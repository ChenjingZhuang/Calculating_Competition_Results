<?php
/**
 * REST API Class
 * Handles REST API endpoints
 *
 * @package Calculating_Competition_Results
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Competition_REST_API {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Seasons
        register_rest_route( 'competition/v1', '/seasons', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_seasons' ),
            'permission_callback' => '__return_true'
        ));
        
        // Cups
        register_rest_route( 'competition/v1', '/cups', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_cups' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'season_id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    }
                )
            )
        ));
        
        // Standings
        register_rest_route( 'competition/v1', '/standings', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_standings' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'cup_id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    }
                ),
                'category' => array(
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || is_string( $param );
                    }
                )
            )
        ));
        
        // Athlete results
        register_rest_route( 'competition/v1', '/athlete/(?P<name>[a-zA-Z0-9\\s\\-\\ä\\ö\\å\\Ä\\Ö\\Å]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_athlete_results' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'name' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return ! empty( $param );
                    }
                ),
                'cup_id' => array(
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || is_numeric( $param );
                    }
                )
            )
        ));
        
        // Save season
        register_rest_route( 'competition/v1', '/seasons', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'save_season' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
        ) );
        
        // Save cup
        register_rest_route( 'competition/v1', '/cups', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'save_cup' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
        ) );

        // Delete season
        register_rest_route( 'competition/v1', '/seasons/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_season' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
        ) );

        // Delete cup
        register_rest_route( 'competition/v1', '/cups/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_cup' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
        ) );
    }
    /**
     * GET /seasons
     * Get all seasons
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_seasons( $request ) {
        $db = new Competition_Database();
        
        $status = $request->get_param( 'status' );
        if ( empty( $status ) ) {
            $status = 'active';
        }
        
        $seasons = $db->get_seasons( $status );
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => $seasons
        ));
    }
    
    /**
     * GET /cups?season_id=X
     * Get cups by season
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_cups( $request ) {
        $db = new Competition_Database();
        
        $season_id = intval( $request->get_param( 'season_id' ) );
        $cups = $db->get_cups( $season_id );
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => $cups
        ));
    }
    
    /**
     * GET /standings?cup_id=X&category=Y
     * Get standings for a cup
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_standings( $request ) {
        $cup_id = intval( $request->get_param( 'cup_id' ) );
        $category = $request->get_param( 'category' );
        
        if ( empty( $cup_id ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Cup ID is required'
            ));
        }
        
        $db = new Competition_Database();
        $cup = $db->get_cup( $cup_id );
        
        if ( ! $cup ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Cup not found'
            ));
        }
        
        // Parse scoring table
        $scoring_table = ! empty( $cup['scoring_table'] ) 
            ? json_decode( $cup['scoring_table'], true ) 
            : array(
                1 => 100,
                2 => 85,
                3 => 75,
                4 => 65,
                5 => 55
            );
        
        // Calculate standings
        $calculator = new Competition_Points_Calculator();
        $standings = $calculator->calculate_standings(
            $cup_id,
            $cup['counted_events_limit'],
            $scoring_table,
            $category
        );
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => array(
                'cup' => $cup,
                'standings' => $standings
            )
        ));
    }
    
    /**
     * GET /athlete/{name}?cup_id=X
     * Get athlete results
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_athlete_results( $request ) {
        $athlete_name = sanitize_text_field( $request->get_param( 'name' ) );
        $cup_id = intval( $request->get_param( 'cup_id' ) );
        
        if ( empty( $athlete_name ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Athlete name is required'
            ));
        }
        
        $db = new Competition_Database();
        
        if ( $cup_id > 0 ) {
            $results = $db->get_athlete_results( $athlete_name, $cup_id );
        } else {
            // Get all results for athlete
            global $wpdb;
            $table = $wpdb->prefix . 'competition_results';
            $sql = "SELECT * FROM $table WHERE athlete_name = %s ORDER BY event_date DESC";
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $athlete_name ), ARRAY_A );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => array(
                'athlete_name' => $athlete_name,
                'results' => $results
            )
        ));
    }
    
    /**
     * Save season
     */
    public function save_season( $request ) {
        $data = $request->get_json_params();
        $name = sanitize_text_field( $data['name'] ?? '' );

        if ( empty( $name ) ) {
            return new WP_Error(
                'missing_season_name',
                'Season name is required.',
                array( 'status' => 400 )
            );
        }

        $season = array(
            'name'       => $name,
            'start_date' => sanitize_text_field( $data['start_date'] ?? '' ),
            'end_date'   => sanitize_text_field( $data['end_date'] ?? '' ),
            'status'     => sanitize_key( $data['status'] ?? 'active' ),
        );

        if ( ! empty( $data['id'] ) ) {
            $season['id'] = absint( $data['id'] );
        }

        $db     = new Competition_Database();
        $result = $db->save_season( $season );

        if ( false === $result ) {
            return new WP_Error(
                'season_save_failed',
                'Season could not be saved.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'data'    => $result,
        ) );
    }
    
    /**
     * Save cup
     */
    public function save_cup( $request ) {
        $data = $request->get_json_params();
        $name = sanitize_text_field( $data['name'] ?? '' );

        if ( empty( $name ) ) {
            return new WP_Error(
                'missing_cup_name',
                'Cup name is required.',
                array( 'status' => 400 )
            );
        }

        $season_id = absint( $data['season_id'] ?? 0 );
        if ( $season_id < 1 ) {
            return new WP_Error(
                'missing_season_id',
                'A valid season ID is required.',
                array( 'status' => 400 )
            );
        }

        $cup = array(
            'name'                  => $name,
            'season_id'             => $season_id,
            'category'              => sanitize_text_field( $data['category'] ?? '' ),
            'scoring_table'         => wp_json_encode( $data['scoring_table'] ?? array() ),
            'counted_events_limit'  => absint( $data['counted_events_limit'] ?? 3 ),
            'status'                => sanitize_key( $data['status'] ?? 'active' ),
        );

        if ( ! empty( $data['id'] ) ) {
            $cup['id'] = absint( $data['id'] );
        }

        $db     = new Competition_Database();
        $result = $db->save_cup( $cup );

        if ( false === $result ) {
            return new WP_Error(
                'cup_save_failed',
                'Cup could not be saved.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'data'    => $result,
        ) );
    }

    /**
     * Delete season.
     */
    public function delete_season( $request ) {
        $db     = new Competition_Database();
        $result = $db->delete_season( absint( $request['id'] ) );

        if ( ! $result ) {
            return new WP_Error(
                'season_delete_failed',
                'Season could not be deleted.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Delete cup.
     */
    public function delete_cup( $request ) {
        $db     = new Competition_Database();
        $result = $db->delete_cup( absint( $request['id'] ) );

        if ( ! $result ) {
            return new WP_Error(
                'cup_delete_failed',
                'Cup could not be deleted.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }
}