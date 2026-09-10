<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Competition_Results_Calculator {

	public function calculate_rider_total( $race_results, $counted_limit, $scoring_table ) {
		$scored_results = array();
		$place_counts   = array_fill( 1, 16, 0 );

		foreach ( $race_results as $race_result ) {
			$placement = $race_result['placement'] ?? null;
			$points = 0;
			if ( array_key_exists( 'points_override', $race_result ) && null !== $race_result['points_override'] ) {
				$points = max( 0, (float) $race_result['points_override'] );
			} elseif ( is_numeric( $placement ) ) {
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

		$counted_results = array_slice( $scored_results, 0, max( 0, (int) $counted_limit ) );
		$total_score = 0;
		foreach ( $counted_results as $result ) {
			$total_score += $result['points'];
			if ( is_numeric( $result['placement'] ) && $result['placement'] >= 1 && $result['placement'] <= 16 ) {
				$place_counts[ (int) $result['placement'] ]++;
			}
		}

		if ( floor( $total_score ) === $total_score ) {
			$total_score = (int) $total_score;
		}

		return array( 'total_score' => $total_score, 'place_counts' => $place_counts, 'counted_results' => $counted_results );
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
			for ( $place = 1; $place <= 16; $place++ ) {
				$count = ( $second['place_counts'][ $place ] ?? 0 ) <=> ( $first['place_counts'][ $place ] ?? 0 );
				if ( 0 !== $count ) {
					return $count;
				}
			}
			return 0;
		} );
		return $riders;
	}

	/**
	 * Assign competition ranks after standings have been sorted.
	 */
	public function assign_competition_ranks( $sorted ) {
		foreach ( $sorted as $index => &$rider ) {
			if ( 0 === $index ) {
				$rider['rank'] = 1;
				continue;
			}

			$previous = $sorted[ $index - 1 ];
			$same_score = ( $rider['total_score'] ?? 0 ) === ( $previous['total_score'] ?? 0 );
			$same_places = ( $rider['place_counts'] ?? array() ) === ( $previous['place_counts'] ?? array() );
			$same_head_to_head = ( $rider['head_to_head_wins'] ?? 0 ) === ( $previous['head_to_head_wins'] ?? 0 );
			$rider['rank'] = ( $same_score && $same_places && $same_head_to_head ) ? $previous['rank'] : $index + 1;
		}
		unset( $rider );

		return $sorted;
	}
}
