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

        // Competitions
        register_rest_route( 'competition/v1', '/competitions', array(
            'methods'             => 'GET',
            'callback'            => function( $request ) {
                $db = new Competition_Database();
                return rest_ensure_response( array(
                    'success' => true,
                    'data' => $db->get_competitions( absint( $request->get_param( 'cup_id' ) ) ),
                ) );
            },
            'permission_callback' => '__return_true',
            'args'                => array(
                'cup_id' => array(
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && absint( $param ) > 0;
                    },
                ),
            ),
        ));

        register_rest_route( 'competition/v1', '/competitions', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'save_competition' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ));

        register_rest_route( 'competition/v1', '/competitions/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_competition' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ));

        register_rest_route( 'competition/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_categories' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'discipline_id' => array( 'required' => true, 'validate_callback' => function( $param ) { return is_numeric( $param ) && absint( $param ) > 0; } ),
            ),
        ));

        register_rest_route( 'competition/v1', '/categories', array(
            'methods' => 'POST',
            'callback' => array( $this, 'save_category' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ));

        register_rest_route( 'competition/v1', '/categories/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array( $this, 'delete_category' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ));

        register_rest_route( 'competition/v1', '/results', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_competition_results' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
            'args' => array(
                'competition_id' => array( 'required' => true, 'validate_callback' => function( $param ) { return is_numeric( $param ) && absint( $param ) > 0; } ),
            ),
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

        // Download standings as CSV.
        register_rest_route( 'competition/v1', '/standings/export', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'export_standings' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'cup_id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && absint( $param ) > 0;
                    },
                ),
                'category' => array(
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || is_string( $param );
                    },
                ),
            ),
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

        // Adjust result points
        register_rest_route( 'competition/v1', '/results/(?P<id>\d+)/adjustment', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'adjust_result_points' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
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
                1 => 30,
                2 => 25,
                3 => 21,
                4 => 18,
                5 => 16,
                6 => 14,
                7 => 12,
                8 => 10,
                9 => 8,
                10 => 7,
                11 => 6,
                12 => 5,
                13 => 4,
                14 => 3,
                15 => 2,
                16 => 1,
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
     * Export the same standings data as a downloadable CSV file.
     */
    public function export_standings( $request ) {
        $response = $this->get_standings( $request );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $payload = $response->get_data();
        if ( empty( $payload['success'] ) ) {
            return new WP_Error( 'standings_export_failed', $payload['message'] ?? 'Standings could not be exported.', array( 'status' => 400 ) );
        }

        $csv_rows = array();
        $csv_rows[] = array( 'Rank', 'Category', 'Athlete', 'Club', '1st', '2nd', '3rd', 'Total' );
        foreach ( $payload['data']['standings'] as $standing ) {
            $counts = $standing['place_counts'] ?? array();
            $csv_rows[] = array(
                $standing['rank'] ?? '',
                $standing['category'] ?? '',
                $standing['athlete_name'] ?? '',
                $standing['club'] ?? '',
                $counts[1] ?? 0,
                $counts[2] ?? 0,
                $counts[3] ?? 0,
                $standing['total_score'] ?? 0,
            );
        }

        $csv_lines = array_map( static function ( $row ) {
            return implode( ';', array_map( static function ( $value ) {
                return '"' . str_replace( '"', '""', (string) $value ) . '"';
            }, $row ) );
        }, $csv_rows );
        $csv = implode( "\r\n", $csv_lines ) . "\r\n";

        $response = new WP_REST_Response( "\xEF\xBB\xBF" . $csv, 200 );
        $response->header( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->header( 'Content-Disposition', 'attachment; filename="competition-standings.csv"' );
        return $response;
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
        
        $results = $db->get_athlete_results( $athlete_name, $cup_id );
        
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
        $year = absint( $data['year'] ?? 0 );
        $name = sanitize_text_field( $data['name'] ?? '' );

        if ( $year < 1 && preg_match( '/\b(19|20)\d{2}\b/', $name, $matches ) ) {
            $year = absint( $matches[0] );
        }

        if ( $year < 1 ) {
            return new WP_Error(
                'missing_season_year',
                'A valid season year is required.',
                array( 'status' => 400 )
            );
        }

        $season = array(
            'year'   => $year,
            'status' => sanitize_key( $data['status'] ?? 'active' ),
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
     * Save competition.
     */
    public function save_competition( $request ) {
        $data = $request->get_json_params();
        $name = sanitize_text_field( $data['name'] ?? '' );
        $cup_id = absint( $data['cup_id'] ?? 0 );
        $event_date = sanitize_text_field( $data['event_date'] ?? '' );
        $status = sanitize_text_field( $data['status'] ?? 'Upcoming' );

        if ( empty( $name ) || ! $cup_id || empty( $event_date ) ) {
            return new WP_Error( 'invalid_competition', 'Competition name, cup and event date are required.', array( 'status' => 400 ) );
        }

        $date = DateTime::createFromFormat( 'Y-m-d', $event_date );
        if ( ! $date || $date->format( 'Y-m-d' ) !== $event_date ) {
            return new WP_Error( 'invalid_competition_date', 'A valid event date is required.', array( 'status' => 400 ) );
        }

        if ( ! in_array( $status, array( 'Upcoming', 'Completed', 'Cancelled' ), true ) ) {
            return new WP_Error( 'invalid_competition_status', 'Invalid competition status.', array( 'status' => 400 ) );
        }

        $db = new Competition_Database();
        if ( ! $db->get_cup( $cup_id ) ) {
            return new WP_Error( 'cup_not_found', 'The selected cup was not found.', array( 'status' => 400 ) );
        }

        $competition = array(
            'name'        => $name,
            'cup_id'      => $cup_id,
            'event_date'  => $event_date,
            'location'    => $data['location'] ?? '',
            'result_link' => $data['result_link'] ?? '',
            'status'      => $status,
        );
        if ( ! empty( $data['id'] ) ) {
            $competition['id'] = absint( $data['id'] );
        }

        $result = $db->save_competition( $competition );
        if ( false === $result ) {
            return new WP_Error( 'competition_save_failed', 'Competition could not be saved.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'success' => true, 'data' => $result ) );
    }

    /**
     * Get categories for a discipline.
     */
    public function get_categories( $request ) {
        $db = new Competition_Database();
        return rest_ensure_response( array( 'success' => true, 'data' => $db->get_categories( absint( $request['discipline_id'] ) ) ) );
    }

    /**
     * Save category.
     */
    public function save_category( $request ) {
        $data = $request->get_json_params();
        $name = sanitize_text_field( $data['name'] ?? '' );
        $discipline_id = absint( $data['discipline_id'] ?? 0 );
        if ( empty( $name ) || ! $discipline_id ) {
            return new WP_Error( 'invalid_category', 'Category name and discipline are required.', array( 'status' => 400 ) );
        }

        $db = new Competition_Database();
        $category = array(
            'name' => $name,
            'discipline_id' => $discipline_id,
            'description' => $data['description'] ?? '',
        );
        if ( ! empty( $data['id'] ) ) {
            $category['id'] = absint( $data['id'] );
        }
        $result = $db->save_category( $category );
        if ( false === $result ) {
            return new WP_Error( 'category_save_failed', 'Category could not be saved. It may already exist.', array( 'status' => 400 ) );
        }
        return rest_ensure_response( array( 'success' => true, 'data' => $result ) );
    }

    /**
     * Delete category.
     */
    public function delete_category( $request ) {
        $result = ( new Competition_Database() )->delete_category( absint( $request['id'] ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( ! $result ) {
            return new WP_Error( 'category_delete_failed', 'Category could not be deleted.', array( 'status' => 500 ) );
        }
        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Get competition results for admin corrections.
     */
    public function get_competition_results( $request ) {
        $db = new Competition_Database();
        return rest_ensure_response( array( 'success' => true, 'data' => $db->get_competition_results( absint( $request['competition_id'] ) ) ) );
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

    /**
     * Delete competition.
     */
    public function delete_competition( $request ) {
        $db = new Competition_Database();
        $result = $db->delete_competition( absint( $request['id'] ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( ! $result ) {
            return new WP_Error( 'competition_delete_failed', 'Competition could not be deleted.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Apply and record a manual point adjustment.
     */
    public function adjust_result_points( $request ) {
        $data   = $request->get_json_params();
        $points = isset( $data['adjusted_points'] ) ? intval( $data['adjusted_points'] ) : -1;
        $reason = sanitize_textarea_field( $data['adjustment_reason'] ?? '' );

        if ( $points < 0 || empty( $reason ) ) {
            return new WP_Error(
                'invalid_point_adjustment',
                'Adjusted points and an adjustment reason are required.',
                array( 'status' => 400 )
            );
        }

        $db     = new Competition_Database();
        $result = $db->adjust_result_points( absint( $request['id'] ), $points, $reason, get_current_user_id() );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( array( 'success' => true, 'data' => $result ) );
    }
}