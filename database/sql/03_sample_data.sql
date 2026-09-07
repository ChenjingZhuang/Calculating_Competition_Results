USE cycling_cup_system;

-- 1. Season
INSERT INTO season (year, status)
VALUES (2026, 'Active');

-- 2. Discipline
INSERT INTO discipline (name)
VALUES ('Mountain Biking');

-- 3. Club
INSERT INTO club (name, abbreviation)
VALUES ('Jyväskylän Pyöräilyseura', 'JyPS');

-- 4. Scoring Rule
INSERT INTO scoring_rule (name, description)
VALUES ('Standard MTB Scoring', 'Standard position-based scoring rule.');

-- 5. Scoring Rule Details
INSERT INTO scoring_rule_detail (scoring_rule_id, placement, points)
VALUES
(1, 1, 30),
(1, 2, 25),
(1, 3, 22);

-- 6. Cup
INSERT INTO cup (
    season_id,
    discipline_id,
    name,
    scoring_rule_id,
    counted_events,
    status
)
VALUES (
    1,
    1,
    'XCO Cup',
    1,
    5,
    'Draft'
);

-- 7. Category
INSERT INTO category (discipline_id, name)
VALUES (1, 'N Elite');

-- 8. Competition
INSERT INTO competition (
    cup_id,
    name,
    event_date,
    location,
    status
)
VALUES (
    1,
    'Hyvinkää XCO',
    '2026-05-10',
    'Hyvinkää',
    'Completed'
);

-- 9. Rider
INSERT INTO rider (
    club_id,
    first_name,
    last_name
)
VALUES (
    1,
    'Sini',
    'Alusniemi'
);

-- 10. Result Import
-- uploaded_by is just a temporary test WordPress user ID.
INSERT INTO result_import (
    competition_id,
    file_name,
    import_status,
    total_records,
    imported_records,
    failed_records,
    uploaded_by
)
VALUES (
    1,
    'test_results.xlsx',
    'Completed',
    1,
    1,
    0,
    1
);

-- 11. Result
INSERT INTO result (
    competition_id,
    rider_id,
    category_id,
    result_import_id,
    placement,
    finish_time,
    status,
    points
)
VALUES (
    1,
    1,
    1,
    1,
    1,
    '2:45:06',
    'Finished',
    30
);