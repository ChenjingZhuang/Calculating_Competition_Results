<?php
/**
 * Calculating Competition Results - Points & Ranking Calculator Engine
 *
 * @package           Calculating_Competition_Results
 * @license           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access to the file outside of WordPress
}

/**
 * Class Competition_Results_Calculator
 * Handles point aggregation, drop-rules, and tie-breaker sorting for cup standings.
 */
class Competition_Results_Calculator {

    /**
     * Calculates individual athlete season points applying the drop-rule.
     *
     * @param array $race_results    Table of results: [['placement' => 1, 'bonus_points' => 0], ...]
     * @param int   $counted_limit   Number of best races to count (X)
     * @param array $scoring_table   Points table: [1 => 100, 2 => 85, 3 => 75, ...]
     * @return array Returns total points and placement history for the tie-breaker
     */
    public function calculate_rider_total( array $race_results, int $counted_limit, array $scoring_table ): array {
        $points_list  = [];
        $place_counts = array_fill( 1, 10, 0 ); // Placements 1–10 for the tie-breaker

        foreach ( $race_results as $result ) {
            $placement = $result['placement'] ?? null;
            $bonus     = isset( $result['bonus_points'] ) ? (int) $result['bonus_points'] : 0;

            if ( is_numeric( $placement ) ) {
                $rank   = (int) $placement;
                $points = $scoring_table[ $rank ] ?? 0;

                if ( $rank >= 1 && $rank <= 10 ) {
                    $place_counts[ $rank ]++;
                }
            } else {
                // DNF, DNS, DSQ -> 0 points
                $points = 0;
            }

            $points_list[] = $points + $bonus;
        }

        // DROP RULE: Sort points in descending order and select the top X
        rsort( $points_list );
        $top_points = array_slice( $points_list, 0, $counted_limit );

        return [
            'total_score'  => array_sum( $top_points ),
            'place_counts' => $place_counts,
        ];
    }

    /**
     * Sorts the category's athletes according to points and tie-breaker rules.
     *
     * @param array $riders_list Table of all athletes in the category with calculated points
     * @return array Sorted standings table
     */
    public function sort_standings( array $riders_list ): array {
        usort( $riders_list, function ( $a, $b ) {
            // 1. Primary criterion: Total points
            if ( $a['total_score'] !== $b['total_score'] ) {
                return $b['total_score'] <=> $a['total_score'];
            }

            // 2. Tie-breaker: Compare 1st place finishes, 2nd place finishes, etc. (up to 10th place)
            for ( $rank = 1; $rank <= 10; $rank++ ) {
                $countA = $a['place_counts'][ $rank ] ?? 0;
                $countB = $b['place_counts'][ $rank ] ?? 0;

                if ( $countA !== $countB ) {
                    return $countB <=> $countA;
                }
            }

            return 0; // Tied positions
        } );

        return $riders_list;
    }
    
    /**
     * Generate standings for a cup
     *
     * @param array $all_results   All results from database
     * @param int   $counted_limit Number of counted events
     * @param array $scoring_table Points table
     * @return array Standings with rankings
     */
    public function generate_standings( array $all_results, int $counted_limit, array $scoring_table ): array {
        // Group by athlete
        $athletes = array();
        
        foreach ( $all_results as $result ) {
            $name = $result['athlete_name'];
            
            if ( ! isset( $athletes[ $name ] ) ) {
                $athletes[ $name ] = array(
                    'athlete_name' => $name,
                    'club' => $result['club'] ?? '',
                    'race_results' => array()
                );
            }
            
            $athletes[ $name ]['race_results'][] = array(
                'placement' => $result['placement'],
                'bonus_points' => 0
            );
        }
        
        // Calculate points for each athlete
        foreach ( $athletes as $name => &$athlete ) {
            $calc = $this->calculate_rider_total( 
                $athlete['race_results'], 
                $counted_limit, 
                $scoring_table 
            );
            
            $athlete['total_score'] = $calc['total_score'];
            $athlete['place_counts'] = $calc['place_counts'];
            $athlete['events'] = count( $athlete['race_results'] );
        }
        
        // Convert to array and sort
        $riders_list = array_values( $athletes );
        $sorted = $this->sort_standings( $riders_list );
        
        // Add rank
        foreach ( $sorted as $rank => &$rider ) {
            $rider['rank'] = $rank + 1;
        }
        
        return $sorted;
    }
}