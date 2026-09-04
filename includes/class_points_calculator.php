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
     * @param array $scoring_table   Points table: [1 => 100, 2 => 85, ...]
     * @param string $category       Category filter (optional)
     * @return array Standings
     */
    public function calculate_standings( $cup_id, $counted_limit, $scoring_table, $category = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'competition_results';
        
        // Get all results for this cup
        $where = "cup_id = %d";
        $values = array( $cup_id );
        
        if ( ! empty( $category ) ) {
            $where .= " AND category = %s";
            $values[] = $category;
        }
        
        $sql = "SELECT athlete_name, club, placement, points, status, event_name, event_date 
                FROM $table WHERE $where ORDER BY event_date";
        
        $all_results = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
        
        if ( empty( $all_results ) ) {
            return array();
        }
        
        // Group by athlete
        $athletes = array();
        foreach ( $all_results as $result ) {
            $name = $result['athlete_name'];
            
            if ( ! isset( $athletes[ $name ] ) ) {
                $athletes[ $name ] = array(
                    'athlete_name' => $name,
                    'club' => $result['club'],
                    'race_results' => array(),
                    'total_score' => 0,
                    'place_counts' => array_fill( 1, 10, 0 ),
                    'events' => 0
                );
            }
            
            $athletes[ $name ]['race_results'][] = array(
                'placement' => $result['placement'],
                'bonus_points' => 0,
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
        
        // Convert to array and sort
        $riders_list = array_values( $athletes );
        $sorted = $calculator->sort_standings( $riders_list );
        
        // Add rank
        foreach ( $sorted as $rank => &$rider ) {
            $rider['rank'] = $rank + 1;
        }
        
        return $sorted;
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
        $table = $wpdb->prefix . 'competition_results';
        
        $sql = "SELECT placement, points, status, event_name, event_date 
                FROM $table 
                WHERE cup_id = %d AND athlete_name = %s 
                ORDER BY event_date";
        
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
        global $wpdb;
        $table = $wpdb->prefix . 'competition_results';
        
        $updated = 0;
        foreach ( $standings as $athlete ) {
            // Update athlete's total points
            // (This is optional - you might want to store totals separately)
        }
        
        return count( $standings );
    }
}