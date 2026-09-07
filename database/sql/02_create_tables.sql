USE cycling_cup_system;
CREATE TABLE season (
    season_id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    status ENUM('Active', 'Archived') NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (year)
);

CREATE TABLE discipline (
    discipline_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (name)
);

CREATE TABLE club (
    club_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    abbreviation VARCHAR(20) NULL,
    location VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (name),
    UNIQUE (abbreviation)
);

CREATE TABLE scoring_rule (
    scoring_rule_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (name)
);

CREATE TABLE scoring_rule_detail (
    scoring_rule_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    scoring_rule_id INT NOT NULL,
    placement INT NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (scoring_rule_id, placement),

	CONSTRAINT chk_scoring_detail_placement
        CHECK (placement > 0),
        
	CONSTRAINT chk_scoring_detail_points
        CHECK (points >= 0),
        
    CONSTRAINT fk_scoring_rule_detail_rule
        FOREIGN KEY (scoring_rule_id)
        REFERENCES scoring_rule(scoring_rule_id)
);

CREATE TABLE cup (
    cup_id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    discipline_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    scoring_rule_id INT NOT NULL,
    counted_events INT NOT NULL,
    status ENUM('Draft', 'Published', 'Archived') NOT NULL DEFAULT 'Draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

	CONSTRAINT chk_cup_counted_events
        CHECK (counted_events > 0),
        
    CONSTRAINT fk_cup_season
        FOREIGN KEY (season_id)
        REFERENCES season(season_id),

    CONSTRAINT fk_cup_discipline
        FOREIGN KEY (discipline_id)
        REFERENCES discipline(discipline_id),

    CONSTRAINT fk_cup_scoring_rule
        FOREIGN KEY (scoring_rule_id)
        REFERENCES scoring_rule(scoring_rule_id)
);

CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    discipline_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (discipline_id, name),

    CONSTRAINT fk_category_discipline
        FOREIGN KEY (discipline_id)
        REFERENCES discipline(discipline_id)
);

CREATE TABLE competition (
    competition_id INT AUTO_INCREMENT PRIMARY KEY,
    cup_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(150) NULL,
    result_link VARCHAR(255) NULL,
    status ENUM('Upcoming', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Upcoming',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_competition_cup
        FOREIGN KEY (cup_id)
        REFERENCES cup(cup_id)
);

CREATE TABLE rider (
    rider_id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_rider_club
        FOREIGN KEY (club_id)
        REFERENCES club(club_id)
);

CREATE TABLE result_import (
    result_import_id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    import_status ENUM('Pending', 'Completed', 'Failed')
        NOT NULL DEFAULT 'Pending',
    total_records INT NOT NULL DEFAULT 0,
    imported_records INT NOT NULL DEFAULT 0,
    failed_records INT NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

	CONSTRAINT chk_result_import_total
        CHECK (total_records >= 0),

    CONSTRAINT chk_result_import_imported
        CHECK (imported_records >= 0),

    CONSTRAINT chk_result_import_failed
        CHECK (failed_records >= 0),
        
    CONSTRAINT fk_result_import_competition
        FOREIGN KEY (competition_id)
        REFERENCES competition(competition_id)
);

CREATE TABLE result (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    rider_id INT NOT NULL,
    category_id INT NOT NULL,
    result_import_id INT NOT NULL,
    placement INT NULL,
    finish_time VARCHAR(30) NULL,
    status ENUM('Finished', 'DNS', 'DNF', 'DSQ')
        NOT NULL DEFAULT 'Finished',
    points INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (competition_id, rider_id, category_id),

	CONSTRAINT chk_result_placement
        CHECK (placement IS NULL OR placement > 0),

    CONSTRAINT chk_result_points
        CHECK (points >= 0),
        
    CONSTRAINT fk_result_competition
        FOREIGN KEY (competition_id)
        REFERENCES competition(competition_id),

    CONSTRAINT fk_result_rider
        FOREIGN KEY (rider_id)
        REFERENCES rider(rider_id),

    CONSTRAINT fk_result_category
        FOREIGN KEY (category_id)
        REFERENCES category(category_id),

    CONSTRAINT fk_result_import
        FOREIGN KEY (result_import_id)
        REFERENCES result_import(result_import_id)
);

CREATE TABLE point_adjustment (
    point_adjustment_id INT AUTO_INCREMENT PRIMARY KEY,
    result_id INT NOT NULL,
    original_points INT NOT NULL,
    adjusted_points INT NOT NULL,
    adjustment_reason TEXT NOT NULL,
    adjusted_by BIGINT UNSIGNED NOT NULL,
    adjustment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

	CONSTRAINT chk_adjustment_original_points
        CHECK (original_points >= 0),

    CONSTRAINT chk_adjustment_adjusted_points
        CHECK (adjusted_points >= 0),
        
    CONSTRAINT fk_point_adjustment_result
        FOREIGN KEY (result_id)
        REFERENCES result(result_id)
);