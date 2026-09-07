<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Competition_Results_Calculator {

	public function calculate_rider_total( $race_results, $counted_limit, $scoring_table ) {
		$scored_results = array();
		$place_counts   = array_fill( 1, 10, 0 );

		foreach ( $race_results as $race_result ) {
			$placement = $race_result['placement'] ?? null;
			$points = 0;
			if ( is_numeric( $placement ) ) {
				$placement = (int) $placement;
				$points = (float) ( $scoring_table[ $placement ] ?? 0 );
				if ( $placement >= 16 && ! isset( $scoring_table[ $placement ] ) ) {
					$points = (float) ( $scoring_table[16] ?? 1 );
				}
			}
			$points   += (float) ( $race_result['bonus_points'] ?? 0 );
			$scored_results[] = array( 'placement' => $placement, 'points' => $points );
		}

		usort( $scored_results, static function ( $first, $second ) {
			return $second['points'] <=> $first['points'];
		} );

		$total_score = 0;
		foreach ( array_slice( $scored_results, 0, max( 0, (int) $counted_limit ) ) as $result ) {
			$total_score += $result['points'];
			if ( is_numeric( $result['placement'] ) && $result['placement'] >= 1 && $result['placement'] <= 10 ) {
				$place_counts[ (int) $result['placement'] ]++;
			}
		}

		if ( floor( $total_score ) === $total_score ) {
			$total_score = (int) $total_score;
		}

		return array( 'total_score' => $total_score, 'place_counts' => $place_counts );
	}

	public function sort_standings( $riders ) {
		usort( $riders, static function ( $first, $second ) {
			$score = ( $second['total_score'] ?? 0 ) <=> ( $first['total_score'] ?? 0 );
			if ( 0 !== $score ) {
				return $score;
			}
			$head_to_head = ( $second['head_to_head_wins'] ?? 0 ) <=> ( $first['head_to_head_wins'] ?? 0 );
			if ( 0 !== $head_to_head ) {
				return $head_to_head;
			}
			for ( $place = 1; $place <= 10; $place++ ) {
				$count = ( $second['place_counts'][ $place ] ?? 0 ) <=> ( $first['place_counts'][ $place ] ?? 0 );
				if ( 0 !== $count ) {
					return $count;
				}
			}
			return 0;
		} );
		return $riders;
	}
}
