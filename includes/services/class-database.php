<?php
/**
 * Database table contract for the competition results plugin.
 *
 * The normalized schema in database/sql/02_create_tables.sql is the single
 * source of truth. WordPress supplies the table prefix at runtime.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Competition_Database {

	/**
	 * Create the tables required by the plugin.
	 */
	public function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$tables          = $this->get_tables();

		$sql = array(
			"CREATE TABLE {$tables['season']} (
				season_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				year int NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'Active',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (season_id),
				UNIQUE KEY year (year)
			) $charset_collate",
			"CREATE TABLE {$tables['discipline']} (
				discipline_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (discipline_id),
				UNIQUE KEY name (name)
			) $charset_collate",
			"CREATE TABLE {$tables['scoring_rule']} (
				scoring_rule_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(100) NOT NULL,
				description text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (scoring_rule_id),
				UNIQUE KEY name (name)
			) $charset_collate",
			"CREATE TABLE {$tables['scoring_rule_detail']} (
				scoring_rule_detail_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				scoring_rule_id bigint(20) unsigned NOT NULL,
				placement int NOT NULL,
				points int NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (scoring_rule_detail_id),
				UNIQUE KEY rule_placement (scoring_rule_id, placement)
			) $charset_collate",
			"CREATE TABLE {$tables['cup']} (
				cup_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				season_id bigint(20) unsigned NOT NULL,
				discipline_id bigint(20) unsigned NOT NULL,
				name varchar(100) NOT NULL,
				scoring_rule_id bigint(20) unsigned NOT NULL,
				counted_events int NOT NULL DEFAULT 3,
				status varchar(20) NOT NULL DEFAULT 'Draft',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (cup_id),
				KEY season_id (season_id)
			) $charset_collate",
			"CREATE TABLE {$tables['category']} (
				category_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				discipline_id bigint(20) unsigned NOT NULL,
				name varchar(100) NOT NULL,
				description text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (category_id),
				UNIQUE KEY discipline_category (discipline_id, name)
			) $charset_collate",
			"CREATE TABLE {$tables['competition']} (
				competition_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				cup_id bigint(20) unsigned NOT NULL,
				name varchar(150) NOT NULL,
				event_date date NOT NULL,
				location varchar(150) NULL,
				result_link varchar(255) NULL,
				status varchar(20) NOT NULL DEFAULT 'Upcoming',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (competition_id),
				KEY cup_id (cup_id)
			) $charset_collate",
			"CREATE TABLE {$tables['club']} (
				club_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				abbreviation varchar(20) NULL,
				location varchar(100) NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (club_id),
				UNIQUE KEY name (name)
			) $charset_collate",
			"CREATE TABLE {$tables['rider']} (
				rider_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				club_id bigint(20) unsigned NULL,
				first_name varchar(100) NOT NULL,
				last_name varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (rider_id),
				KEY rider_name (first_name, last_name)
			) $charset_collate",
			"CREATE TABLE {$tables['result_import']} (
				result_import_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				competition_id bigint(20) unsigned NOT NULL,
				file_name varchar(255) NOT NULL,
				upload_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				import_status varchar(20) NOT NULL DEFAULT 'Pending',
				total_records int NOT NULL DEFAULT 0,
				imported_records int NOT NULL DEFAULT 0,
				failed_records int NOT NULL DEFAULT 0,
				error_message text NULL,
				uploaded_by bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (result_import_id)
			) $charset_collate",
			"CREATE TABLE {$tables['result']} (
				result_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				competition_id bigint(20) unsigned NOT NULL,
				rider_id bigint(20) unsigned NOT NULL,
				category_id bigint(20) unsigned NOT NULL,
				result_import_id bigint(20) unsigned NOT NULL,
				placement int NULL,
				finish_time varchar(30) NULL,
				status varchar(20) NOT NULL DEFAULT 'Finished',
				points int NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (result_id),
				KEY competition_id (competition_id),
				KEY rider_id (rider_id),
				UNIQUE KEY competition_rider_category (competition_id, rider_id, category_id)
			) $charset_collate",
			"CREATE TABLE {$tables['point_adjustment']} (
				point_adjustment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				result_id bigint(20) unsigned NOT NULL,
				original_points int NOT NULL,
				adjusted_points int NOT NULL,
				adjustment_reason text NOT NULL,
				adjusted_by bigint(20) unsigned NOT NULL,
				adjustment_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (point_adjustment_id),
				KEY result_id (result_id),
				KEY adjusted_by (adjusted_by)
			) $charset_collate",
		);

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		$this->add_foreign_keys( $tables );
	}

	/**
	 * Add documented application-table relationships once tables exist.
	 */
	private function add_foreign_keys( $tables ) {
		global $wpdb;

		$relationships = array(
			array( 'cup', 'season_id', 'season', 'season_id', 'fk_cup_season' ),
			array( 'cup', 'discipline_id', 'discipline', 'discipline_id', 'fk_cup_discipline' ),
			array( 'cup', 'scoring_rule_id', 'scoring_rule', 'scoring_rule_id', 'fk_cup_scoring_rule' ),
			array( 'competition', 'cup_id', 'cup', 'cup_id', 'fk_competition_cup' ),
			array( 'category', 'discipline_id', 'discipline', 'discipline_id', 'fk_category_discipline' ),
			array( 'rider', 'club_id', 'club', 'club_id', 'fk_rider_club' ),
			array( 'result_import', 'competition_id', 'competition', 'competition_id', 'fk_result_import_competition' ),
			array( 'result', 'competition_id', 'competition', 'competition_id', 'fk_result_competition' ),
			array( 'result', 'rider_id', 'rider', 'rider_id', 'fk_result_rider' ),
			array( 'result', 'category_id', 'category', 'category_id', 'fk_result_category' ),
			array( 'result', 'result_import_id', 'result_import', 'result_import_id', 'fk_result_import' ),
			array( 'scoring_rule_detail', 'scoring_rule_id', 'scoring_rule', 'scoring_rule_id', 'fk_scoring_rule_detail_rule' ),
			array( 'point_adjustment', 'result_id', 'result', 'result_id', 'fk_point_adjustment_result' ),
		);

		foreach ( $relationships as $relationship ) {
			list( $child, $column, $parent, $parent_column, $constraint ) = $relationship;
			$exists = $wpdb->get_var( $wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s',
				$tables[ $child ],
				$constraint
			) );

			if ( $exists ) {
				continue;
			}

			$wpdb->query(
				"ALTER TABLE {$tables[$child]} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$tables[$parent]} ({$parent_column})"
			);
		}
	}

	/**
	 * Get seasons in the API response format.
	 */
	public function get_seasons( $status = 'active' ) {
		global $wpdb;
		$table = $this->get_tables()['season'];
		$sql   = "SELECT season_id AS id, year, CONCAT(year, ' Season') AS name,
					'' AS start_date, '' AS end_date, LOWER(status) AS status
				FROM $table";
		$values = array();

		if ( 'all' !== strtolower( $status ) ) {
			$sql     .= ' WHERE status = %s';
			$values[] = 'active' === strtolower( $status ) ? 'Active' : sanitize_text_field( $status );
		}

		$sql .= ' ORDER BY year DESC';
		return $wpdb->get_results( $values ? $wpdb->prepare( $sql, $values ) : $sql, ARRAY_A );
	}

	/**
	 * Get cups belonging to a season.
	 */
	public function get_cups( $season_id ) {
		global $wpdb;
		$tables = $this->get_tables();
		$sql    = "SELECT cup_id AS id, name, season_id, counted_events AS counted_events_limit,
					LOWER(status) AS status FROM {$tables['cup']} WHERE season_id = %d ORDER BY name";
		return $wpdb->get_results( $wpdb->prepare( $sql, absint( $season_id ) ), ARRAY_A );
	}

	public function get_competitions( $cup_id ) {
		global $wpdb;
		$table = $this->get_tables()['competition'];
		$sql   = "SELECT competition_id AS id, name, cup_id, event_date, status
				FROM $table WHERE cup_id = %d ORDER BY event_date, name";
		return $wpdb->get_results( $wpdb->prepare( $sql, absint( $cup_id ) ), ARRAY_A );
	}

	/**
	 * Get one cup with its scoring rule.
	 */
	public function get_cup( $cup_id ) {
		global $wpdb;
		$tables = $this->get_tables();
		$sql    = "SELECT cup_id AS id, name, season_id, counted_events AS counted_events_limit,
					LOWER(status) AS status, scoring_rule_id
				FROM {$tables['cup']} WHERE cup_id = %d";
		$cup = $wpdb->get_row( $wpdb->prepare( $sql, absint( $cup_id ) ), ARRAY_A );

		if ( ! $cup ) {
			return null;
		}

		$scoring_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT placement, points FROM {$tables['scoring_rule_detail']} WHERE scoring_rule_id = %d ORDER BY placement",
			$cup['scoring_rule_id']
		), ARRAY_A );
		$scoring_table = array();
		foreach ( $scoring_rows as $scoring_row ) {
			$scoring_table[ (int) $scoring_row['placement'] ] = (int) $scoring_row['points'];
		}
		$cup['scoring_table'] = wp_json_encode( $scoring_table );

		return $cup;
	}

	/**
	 * Save a season using the normalized year-based schema.
	 */
	public function save_season( $season ) {
		global $wpdb;
		$table = $this->get_tables()['season'];
		$year  = absint( $season['year'] ?? preg_replace( '/[^0-9]/', '', $season['name'] ?? '' ) );
		if ( $year < 1 ) {
			return false;
		}

		$data = array( 'year' => $year, 'status' => 'active' === strtolower( $season['status'] ?? '' ) ? 'Active' : 'Archived' );
		if ( ! empty( $season['id'] ) ) {
			$wpdb->update( $table, $data, array( 'season_id' => absint( $season['id'] ) ), array( '%d', '%s' ), array( '%d' ) );
			return $wpdb->get_row( $wpdb->prepare( "SELECT season_id AS id, year, CONCAT(year, ' Season') AS name, '' AS start_date, '' AS end_date, LOWER(status) AS status FROM $table WHERE season_id = %d", absint( $season['id'] ) ), ARRAY_A );
		}

		if ( false === $wpdb->insert( $table, $data, array( '%d', '%s' ) ) ) {
			return false;
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT season_id AS id, year, CONCAT(year, ' Season') AS name, '' AS start_date, '' AS end_date, LOWER(status) AS status FROM $table WHERE season_id = %d", absint( $wpdb->insert_id ) ), ARRAY_A );
	}

	/**
	 * Save a cup and its scoring rule.
	 */
	public function save_cup( $cup ) {
		global $wpdb;
		$tables = $this->get_tables();
		$discipline_id = $this->ensure_named_row( $tables['discipline'], 'discipline_id', 'name', 'Mountain Biking' );
		$scoring_table = $cup['scoring_table'] ?? array();
		if ( is_string( $scoring_table ) ) {
			$scoring_table = json_decode( $scoring_table, true );
		}
		if ( empty( $scoring_table ) ) {
			$scoring_table = array( 1 => 30, 2 => 25, 3 => 21, 4 => 18, 5 => 16, 6 => 14, 7 => 12, 8 => 10, 9 => 8, 10 => 7, 11 => 6, 12 => 5, 13 => 4, 14 => 3, 15 => 2, 16 => 1 );
		}
		ksort( $scoring_table );
		$rule_name = 'Scoring ' . md5( wp_json_encode( $scoring_table ) );
		$rule_id = $this->ensure_named_row( $tables['scoring_rule'], 'scoring_rule_id', 'name', $rule_name );
		$has_details = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['scoring_rule_detail']} WHERE scoring_rule_id = %d", $rule_id ) );
		if ( ! $has_details ) {
		foreach ( $scoring_table as $placement => $points ) {
			if ( absint( $placement ) < 1 ) {
				continue;
			}
			$wpdb->insert(
				$tables['scoring_rule_detail'],
				array( 'scoring_rule_id' => $rule_id, 'placement' => absint( $placement ), 'points' => max( 0, (int) $points ) ),
				array( '%d', '%d', '%d' )
			);
		}
		}
		$data = array(
			'season_id' => absint( $cup['season_id'] ),
			'discipline_id' => $discipline_id,
			'name' => sanitize_text_field( $cup['name'] ),
			'scoring_rule_id' => $rule_id,
			'counted_events' => max( 1, absint( $cup['counted_events_limit'] ?? 3 ) ),
			'status' => 'active' === strtolower( $cup['status'] ?? '' ) ? 'Published' : 'Draft',
		);
		if ( ! empty( $cup['id'] ) ) {
			$wpdb->update( $tables['cup'], $data, array( 'cup_id' => absint( $cup['id'] ) ) );
			return $this->get_cup( $cup['id'] );
		}
		if ( false === $wpdb->insert( $tables['cup'], $data ) ) {
			return false;
		}
		return $this->get_cup( $wpdb->insert_id );
	}

	public function get_athlete_results( $athlete_name, $cup_id ) {
		global $wpdb;
		$tables = $this->get_tables();
		$sql    = "SELECT r.*, CONCAT(rd.first_name, ' ', rd.last_name) AS athlete_name,
					c.name AS club, cp.name AS cup_name, co.name AS event_name, co.event_date
				FROM {$tables['result']} r
				JOIN {$tables['rider']} rd ON r.rider_id = rd.rider_id
				LEFT JOIN {$tables['club']} c ON rd.club_id = c.club_id
				JOIN {$tables['competition']} co ON r.competition_id = co.competition_id
				JOIN {$tables['cup']} cp ON co.cup_id = cp.cup_id
				WHERE CONCAT(rd.first_name, ' ', rd.last_name) = %s
				  AND ( %d = 0 OR cp.cup_id = %d )
				ORDER BY co.event_date DESC";
		$cup_id = absint( $cup_id );
		return $wpdb->get_results( $wpdb->prepare( $sql, sanitize_text_field( $athlete_name ), $cup_id, $cup_id ), ARRAY_A );
	}

	/**
	 * Insert normalized result rows supplied by an importer.
	 */
	public function save_results( $results, $metadata = array() ) {
		global $wpdb;
		$tables = $this->get_tables();
		$count  = 0;
		$competition_id = absint( $results[0]['competition_id'] ?? 0 );
		if ( ! $competition_id ) {
			return new WP_Error( 'missing_competition_id', 'A competition ID is required.' );
		}

		$competition = $wpdb->get_row( $wpdb->prepare(
			"SELECT co.competition_id, co.cup_id, cp.discipline_id
			 FROM {$tables['competition']} co
			 JOIN {$tables['cup']} cp ON co.cup_id = cp.cup_id
			 WHERE co.competition_id = %d",
			$competition_id
		), ARRAY_A );
		if ( ! $competition ) {
			return new WP_Error( 'competition_not_found', 'The selected competition was not found.' );
		}

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tables['result']} WHERE competition_id = %d",
			$competition_id
		) );
		if ( $existing ) {
			return new WP_Error( 'duplicate_import', 'Results already exist for this competition.' );
		}

		$wpdb->query( 'START TRANSACTION' );
		$prepared_results = array();
		foreach ( (array) $results as $result ) {
			$club_id = $this->ensure_club( $result['club'] ?? '' );
			$rider_id = $this->ensure_rider( $result['first_name'] ?? '', $result['last_name'] ?? '', $club_id );
			$category_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT category_id FROM {$tables['category']} WHERE discipline_id = %d AND name = %s",
				$competition['discipline_id'],
				trim( (string) ( $result['category'] ?? '' ) )
			) );

			if ( ! $rider_id || ! $category_id ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'category_not_found', 'An imported category was not found for this competition.' );
			}

			$prepared_results[] = array(
				'competition_id' => $competition_id,
				'rider_id' => $rider_id,
				'category_id' => absint( $category_id ),
				'placement' => isset( $result['placement'] ) && is_numeric( $result['placement'] ) ? absint( $result['placement'] ) : null,
				'finish_time' => sanitize_text_field( $result['finish_time'] ?? '' ),
				'status' => sanitize_text_field( $result['status'] ?? 'Finished' ),
				'points' => (int) ( $result['points'] ?? 0 ),
			);
		}

		$wpdb->insert(
			$tables['result_import'],
			array(
				'competition_id' => $competition_id,
				'file_name' => sanitize_file_name( $metadata['file_name'] ?? 'WordPress upload' ),
				'import_status' => 'Pending',
				'total_records' => count( $results ) + absint( $metadata['failed_records'] ?? 0 ),
				'uploaded_by' => get_current_user_id(),
			),
			array( '%d', '%s', '%s', '%d', '%d' )
		);
		$import_id = absint( $wpdb->insert_id );
		if ( ! $competition_id || ! $import_id ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'import_record_failed', 'The import record could not be created.' );
		}

		foreach ( $prepared_results as $prepared_result ) {
			$data = array(
				'competition_id'  => $prepared_result['competition_id'],
				'rider_id'        => $prepared_result['rider_id'],
				'category_id'     => $prepared_result['category_id'],
				'result_import_id' => $import_id,
				'placement'       => $prepared_result['placement'],
				'finish_time'     => $prepared_result['finish_time'],
				'status'          => $prepared_result['status'],
				'points'          => $prepared_result['points'],
			);

			if ( false !== $wpdb->insert( $tables['result'], $data ) ) {
				$count++;
			} else {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'result_insert_failed', 'A result could not be inserted.' );
			}
		}

		$wpdb->update(
			$tables['result_import'],
			array( 'import_status' => 'Completed', 'imported_records' => $count, 'failed_records' => absint( $metadata['failed_records'] ?? 0 ) ),
			array( 'result_import_id' => $import_id ),
			array( '%s', '%d', '%d' ),
			array( '%d' )
		);
		$wpdb->query( 'COMMIT' );
		return $count;
	}

	private function ensure_club( $name ) {
		$name = trim( (string) $name );
		return $name ? $this->ensure_named_row( $this->get_tables()['club'], 'club_id', 'name', $name ) : 0;
	}

	private function ensure_rider( $first_name, $last_name, $club_id ) {
		global $wpdb;
		$table = $this->get_tables()['rider'];
		$where = array( 'first_name' => sanitize_text_field( $first_name ), 'last_name' => sanitize_text_field( $last_name ) );
		if ( $club_id ) {
			$where['club_id'] = $club_id;
		} else {
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT rider_id FROM $table WHERE first_name = %s AND last_name = %s AND club_id IS NULL LIMIT 1", $where['first_name'], $where['last_name'] ) );
			if ( $existing ) {
				return absint( $existing );
			}
		}
		if ( $club_id ) {
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT rider_id FROM $table WHERE first_name = %s AND last_name = %s AND club_id = %d LIMIT 1", $where['first_name'], $where['last_name'], $club_id ) );
			if ( $existing ) {
				return absint( $existing );
			}
		}
		$wpdb->insert( $table, array( 'first_name' => $where['first_name'], 'last_name' => $where['last_name'], 'club_id' => $club_id ?: null ) );
		return absint( $wpdb->insert_id );
	}

	public function update_result( $result_id, $data ) {
		global $wpdb;
		$update = array(
			'placement' => absint( $data['placement'] ?? 0 ) ?: null,
			'points'    => (int) ( $data['points'] ?? 0 ),
			'status'    => sanitize_text_field( $data['status'] ?? 'Finished' ),
		);
		return false !== $wpdb->update(
			$this->get_tables()['result'],
			$update,
			array( 'result_id' => absint( $result_id ) ),
			array( '%d', '%d', '%s' ),
			array( '%d' )
		);
	}

	public function delete_result( $result_id ) {
		global $wpdb;
		return false !== $wpdb->delete( $this->get_tables()['result'], array( 'result_id' => absint( $result_id ) ), array( '%d' ) );
	}

	public function adjust_result_points( $result_id, $adjusted_points, $reason, $user_id ) {
		global $wpdb;
		$tables = $this->get_tables();
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT result_id, points FROM {$tables['result']} WHERE result_id = %d",
			absint( $result_id )
		), ARRAY_A );

		if ( ! $result ) {
			return new WP_Error( 'result_not_found', 'The result to adjust was not found.', array( 'status' => 404 ) );
		}

		$original_points = (int) $result['points'];
		$adjusted_points = max( 0, (int) $adjusted_points );
		$wpdb->query( 'START TRANSACTION' );
		$inserted = $wpdb->insert(
			$tables['point_adjustment'],
			array(
				'result_id' => absint( $result_id ),
				'original_points' => $original_points,
				'adjusted_points' => $adjusted_points,
				'adjustment_reason' => sanitize_textarea_field( $reason ),
				'adjusted_by' => absint( $user_id ),
			),
			array( '%d', '%d', '%d', '%s', '%d' )
		);

		if ( false === $inserted || false === $wpdb->update(
			$tables['result'],
			array( 'points' => $adjusted_points ),
			array( 'result_id' => absint( $result_id ) ),
			array( '%d' ),
			array( '%d' )
		) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'point_adjustment_failed', 'The point adjustment could not be saved.', array( 'status' => 500 ) );
		}

		$wpdb->query( 'COMMIT' );
		return array(
			'result_id' => absint( $result_id ),
			'original_points' => $original_points,
			'adjusted_points' => $adjusted_points,
		);
	}

	private function ensure_named_row( $table, $id_column, $name_column, $name ) {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT $id_column FROM $table WHERE $name_column = %s", $name ) );
		if ( $id ) {
			return absint( $id );
		}
		$wpdb->insert( $table, array( $name_column => $name ), array( '%s' ) );
		return absint( $wpdb->insert_id );
	}

	public function delete_season( $season_id ) {
		global $wpdb;
		return false !== $wpdb->delete( $this->get_tables()['season'], array( 'season_id' => absint( $season_id ) ), array( '%d' ) );
	}

	public function delete_cup( $cup_id ) {
		global $wpdb;
		return false !== $wpdb->delete( $this->get_tables()['cup'], array( 'cup_id' => absint( $cup_id ) ), array( '%d' ) );
	}

	/**
	 * Return the canonical table names used by the plugin.
	 *
	 * @return array<string, string>
	 */
	public function get_tables() {
		global $wpdb;

		$prefix = $wpdb->prefix;

		return array(
			'season'              => $prefix . 'season',
			'discipline'          => $prefix . 'discipline',
			'club'                => $prefix . 'club',
			'scoring_rule'        => $prefix . 'scoring_rule',
			'scoring_rule_detail' => $prefix . 'scoring_rule_detail',
			'cup'                 => $prefix . 'cup',
			'category'            => $prefix . 'category',
			'competition'         => $prefix . 'competition',
			'rider'               => $prefix . 'rider',
			'result_import'       => $prefix . 'result_import',
			'result'              => $prefix . 'result',
			'point_adjustment'    => $prefix . 'point_adjustment',
		);
	}
}
