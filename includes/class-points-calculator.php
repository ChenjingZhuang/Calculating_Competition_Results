<?php
/**
 * Points Calculator
 * Wrapper for Competition_Results_Calculator with database integration
 *
 * @package Calculating_Competition_Results
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Competition_Points_Calculator
 */
class Competition_Points_Calculator {
    
    /**
     * Calculate standings for a cup
     *
     * @param int   $cup_id          Cup ID
     * @param int   $counted_limit   Number of counted events
    * @param array $scoring_table   Points table: [1 => 30, 2 => 25, ..., 16 => 1]
     * @param string $category       Category filter (optional)
     * @return array Standings
     */
    public function calculate_standings( $cup_id, $counted_limit, $scoring_table, $category = '' ) {
        global $wpdb;
        $tables = ( new Competition_Database() )->get_tables();
        
        // Get all results for this cup
        $where = "cp.cup_id = %d";
        $values = array( $cup_id );
        
        if ( ! empty( $category ) ) {
            $where .= " AND cat.name = %s";
            $values[] = $category;
        }
        
        $sql = "SELECT r.competition_id, r.rider_id, cat.category_id, cat.name AS category_name,
                   CONCAT(rd.first_name, ' ', rd.last_name) AS athlete_name,
                   cl.name AS club, r.placement, r.points, r.status,
                       co.name AS event_name, co.event_date
                FROM {$tables['result']} r
                JOIN {$tables['rider']} rd ON r.rider_id = rd.rider_id
                LEFT JOIN {$tables['club']} cl ON rd.club_id = cl.club_id
                JOIN {$tables['category']} cat ON r.category_id = cat.category_id
                JOIN {$tables['competition']} co ON r.competition_id = co.competition_id
                JOIN {$tables['cup']} cp ON co.cup_id = cp.cup_id
                WHERE $where ORDER BY co.event_date";
        
        $all_results = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
        
        if ( empty( $all_results ) ) {
            return array();
        }
        
        // Group by athlete
        $athletes = array();
        foreach ( $all_results as $result ) {
            $name = $result['category_id'] . ':' . $result['rider_id'];
            
            if ( ! isset( $athletes[ $name ] ) ) {
                $athletes[ $name ] = array(
                    'athlete_name' => $result['athlete_name'],
                    'category_id' => (int) $result['category_id'],
                    'category' => $result['category_name'],
                    'club' => $result['club'],
                    'race_results' => array(),
                    'total_score' => 0,
                    'place_counts' => array_fill( 1, 10, 0 ),
                        'head_to_head_wins' => 0,
                    'events' => 0
                );
            }
            
            $athletes[ $name ]['race_results'][] = array(
                'placement' => $result['placement'],
                'bonus_points' => 0,
                'competition_id' => $result['competition_id'],
                'event_name' => $result['event_name'],
                'event_date' => $result['event_date']
            );
            $athletes[ $name ]['events']++;
        }
        
        // Calculate points for each athlete
        $calculator = new Competition_Results_Calculator();
        
        foreach ( $athletes as $name => &$athlete ) {
            $calc = $calculator->calculate_rider_total( 
                $athlete['race_results'], 
                $counted_limit, 
                $scoring_table 
            );
            
            $athlete['total_score'] = $calc['total_score'];
            $athlete['place_counts'] = $calc['place_counts'];
        }

        $this->calculate_head_to_head_wins( $athletes );
        
        $riders_by_category = array();
        foreach ( $athletes as $athlete ) {
            $riders_by_category[ $athlete['category_id'] ][] = $athlete;
        }

        $standings = array();
        foreach ( $riders_by_category as $category_riders ) {
            $sorted = $calculator->sort_standings( $category_riders );

            foreach ( $sorted as $rank => &$rider ) {
                if ( 0 === $rank ) {
                    $rider['rank'] = 1;
                    continue;
                }

                $previous = $sorted[ $rank - 1 ];
                $same_score = ( $rider['total_score'] ?? 0 ) === ( $previous['total_score'] ?? 0 );
                $same_places = ( $rider['place_counts'] ?? array() ) === ( $previous['place_counts'] ?? array() );
                $same_head_to_head = ( $rider['head_to_head_wins'] ?? 0 ) === ( $previous['head_to_head_wins'] ?? 0 );
                $rider['rank'] = ( $same_score && $same_places && $same_head_to_head ) ? $previous['rank'] : $rank + 1;
            }
            unset( $rider );

            $standings = array_merge( $standings, $sorted );
        }

        return $standings;
    }

    private function calculate_head_to_head_wins( &$athletes ) {
        $names = array_keys( $athletes );
        foreach ( $names as $name ) {
            $athletes[ $name ]['head_to_head_wins'] = 0;
        }

        for ( $first_index = 0; $first_index < count( $names ); $first_index++ ) {
            for ( $second_index = $first_index + 1; $second_index < count( $names ); $second_index++ ) {
                $first_name = $names[ $first_index ];
                $second_name = $names[ $second_index ];
                if ( $athletes[ $first_name ]['total_score'] !== $athletes[ $second_name ]['total_score'] || $athletes[ $first_name ]['category_id'] !== $athletes[ $second_name ]['category_id'] ) {
                    continue;
                }

                $first_events = array();
                foreach ( $athletes[ $first_name ]['race_results'] as $race ) {
                    if ( isset( $race['competition_id'] ) ) {
                        $first_events[ $race['competition_id'] ] = $race['placement'];
                    }
                }
                foreach ( $athletes[ $second_name ]['race_results'] as $race ) {
                    $competition_id = $race['competition_id'] ?? null;
                    if ( null === $competition_id || ! isset( $first_events[ $competition_id ] ) ) {
                        continue;
                    }
                    $first_placement = $first_events[ $competition_id ];
                    $second_placement = $race['placement'];
                    if ( is_numeric( $first_placement ) && is_numeric( $second_placement ) && $first_placement !== $second_placement ) {
                        if ( $first_placement < $second_placement ) {
                            $athletes[ $first_name ]['head_to_head_wins']++;
                        } else {
                            $athletes[ $second_name ]['head_to_head_wins']++;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Calculate points for a single athlete
     *
     * @param string $athlete_name  Athlete name
     * @param int    $cup_id        Cup ID
     * @param int    $counted_limit Number of counted events
     * @param array  $scoring_table Points table
     * @return array Athlete points data
     */
    public function calculate_athlete_points( $athlete_name, $cup_id, $counted_limit, $scoring_table ) {
        global $wpdb;
        $tables = ( new Competition_Database() )->get_tables();
        
        $sql = "SELECT r.placement, r.points, r.status, co.name AS event_name, co.event_date
            FROM {$tables['result']} r
            JOIN {$tables['rider']} rd ON r.rider_id = rd.rider_id
            JOIN {$tables['competition']} co ON r.competition_id = co.competition_id
            JOIN {$tables['cup']} cp ON co.cup_id = cp.cup_id
            WHERE cp.cup_id = %d
              AND CONCAT(rd.first_name, ' ', rd.last_name) = %s
            ORDER BY co.event_date";
        
        $results = $wpdb->get_results( 
            $wpdb->prepare( $sql, $cup_id, $athlete_name ), 
            ARRAY_A 
        );
        
        if ( empty( $results ) ) {
            return array(
                'athlete_name' => $athlete_name,
                'total_score' => 0,
                'events' => 0,
                'race_results' => array()
            );
        }
        
        $calculator = new Competition_Results_Calculator();
        $calc = $calculator->calculate_rider_total( $results, $counted_limit, $scoring_table );
        
        return array(
            'athlete_name' => $athlete_name,
            'total_score' => $calc['total_score'],
            'place_counts' => $calc['place_counts'],
            'events' => count( $results ),
            'race_results' => $results
        );
    }
    
    /**
     * Recalculate all points for a cup
     *
     * @param int   $cup_id        Cup ID
     * @param int   $counted_limit Number of counted events
     * @param array $scoring_table Points table
     * @return int Number of athletes recalculated
     */
    public function recalculate_cup( $cup_id, $counted_limit, $scoring_table ) {
        $standings = $this->calculate_standings( $cup_id, $counted_limit, $scoring_table );
        
        // Update points in database
        foreach ( $standings as $athlete ) {
            // Update athlete's total points
            // (This is optional - you might want to store totals separately)
        }
        
        return count( $standings );
    }
}