<?php
/**
 * WordPress-facing Excel parser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Competition_Excel_Importer {

	public function preview( $file_path ) {
		$parsed = $this->parse_file( $file_path );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		return array(
			'results' => $parsed['valid'],
			'errors'  => $parsed['invalid'],
			'count'   => count( $parsed['valid'] ),
		);
	}

	public function import( $file_path, $competition_id ) {
		$parsed = $this->parse_file( $file_path );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		foreach ( $parsed['valid'] as &$result ) {
			$result['competition_id'] = absint( $competition_id );
		}

		return array(
			'results' => $parsed['valid'],
			'errors'  => $parsed['invalid'],
			'file_name' => basename( $file_path ),
		);
	}

	private function parse_file( $file_path ) {
		if ( ! is_readable( $file_path ) ) {
			return new WP_Error( 'invalid_excel_file', 'The uploaded Excel file could not be read.' );
		}

		$autoload = dirname( __DIR__ ) . '/database/parser/vendor/autoload.php';
		if ( ! file_exists( $autoload ) ) {
			return new WP_Error( 'missing_excel_dependency', 'PhpSpreadsheet is not installed. Run Composer in database/parser.' );
		}
		require_once $autoload;
		require_once dirname( __DIR__ ) . '/database/parser/validator.php';

		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $file_path );
			$rows        = $spreadsheet->getActiveSheet()->toArray( null, true, true, false );
		} catch ( Throwable $error ) {
			return new WP_Error( 'excel_parse_failed', $error->getMessage() );
		}

		$category = null;
		$valid   = array();
		$invalid = array();

		foreach ( $rows as $row ) {
			$row = array_map( static function ( $value ) {
				return trim( (string) ( $value ?? '' ) );
			}, $row );
			while ( $row && '' === $row[0] ) {
				array_shift( $row );
			}
			while ( $row && '' === end( $row ) ) {
				array_pop( $row );
			}
			if ( ! $row ) {
				continue;
			}

			$is_status_row = false;
			foreach ( $row as $value ) {
				if ( in_array( strtoupper( ltrim( $value, '- ' ) ), array( 'DNS', 'DNF', 'DSQ' ), true ) ) {
					$is_status_row = true;
					break;
				}
			}
			$is_category = ! $is_status_row && ! empty( $row[0] ) && ! is_numeric( $row[0] );
			foreach ( $row as $value ) {
				if ( preg_match( '/\bkm\b/i', $value ) ) {
					$is_category = true;
					break;
				}
			}
			if ( $is_category ) {
				$category = $row[0];
				continue;
			}

			$result = null;
			if ( isset( $row[0] ) && is_numeric( $row[0] ) ) {
				$finish_time = null;
				for ( $index = 4; $index < count( $row ); $index++ ) {
					if ( preg_match( '/^\d{1,2}:\d{2}(?::\d{2})?(?:[.,]\d+)?$/', $row[ $index ] ) ) {
						$finish_time = $row[ $index ];
						break;
					}
				}
				$result = array( 'category' => $category, 'placement' => (int) $row[0], 'last_name' => $row[1] ?? '', 'first_name' => $row[2] ?? '', 'club' => $row[3] ?? '', 'finish_time' => $finish_time, 'status' => 'Finished' );
			} else {
				$status = null;
				if ( isset( $row[0] ) && '-' === $row[0] ) {
					array_shift( $row );
				}
				foreach ( $row as $value ) {
					$normalized_value = strtoupper( ltrim( $value, '- ' ) );
					if ( in_array( $normalized_value, array( 'DNS', 'DNF', 'DSQ' ), true ) ) {
						$status = $normalized_value;
						break;
					}
				}
				if ( $status ) {
					$result = array( 'category' => $category, 'placement' => null, 'last_name' => $row[0] ?? '', 'first_name' => $row[1] ?? '', 'club' => ( isset( $row[2] ) && ! in_array( strtoupper( ltrim( $row[2], '- ' ) ), array( 'DNS', 'DNF', 'DSQ' ), true ) ? $row[2] : '' ), 'finish_time' => null, 'status' => $status );
				}
			}

			if ( ! $result ) {
				$invalid[] = array( 'result' => array( 'category' => $category, 'row' => $row ), 'errors' => array( 'Could not identify a result status or placement.' ) );
				continue;
			}
			$validation = validateResult( $result );
			if ( $validation['errors'] ) {
				$invalid[] = array( 'result' => $result, 'errors' => $validation['errors'] );
			} else {
				$valid[] = $result;
			}
		}

		return array( 'valid' => $valid, 'invalid' => $invalid );
	}
}
