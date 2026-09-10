USE cycling_cup_system;

-- View main database data
SELECT * FROM season;
SELECT * FROM discipline;
SELECT * FROM cup;
SELECT * FROM competition;
SELECT * FROM category;
SELECT * FROM club;
SELECT * FROM rider;
SELECT * FROM scoring_rule;
SELECT * FROM scoring_rule_detail;
SELECT * FROM result_import;
SELECT * FROM result;
SELECT * FROM point_adjustment;

-- View recent imports
SELECT *
FROM result_import
ORDER BY result_import_id DESC;

-- View recent results
SELECT *
FROM result
ORDER BY result_id DESC;

-- View results with rider, club and category information
SELECT
    r.result_id,
    r.placement,
    rd.first_name,
    rd.last_name,
    cl.name AS club,
    cat.name AS category,
    r.finish_time,
    r.status,
    r.points
FROM result r
JOIN rider rd
    ON r.rider_id = rd.rider_id
LEFT JOIN club cl
    ON rd.club_id = cl.club_id
JOIN category cat
    ON r.category_id = cat.category_id
ORDER BY
    cat.name,
    r.placement;

-- View complete result information including competition, cup and season
SELECT
    r.result_id,
    rd.first_name,
    rd.last_name,
    cl.name AS club,
    cat.name AS category,
    c.name AS competition,
    cp.name AS cup,
    s.year AS season,
    r.placement,
    r.finish_time,
    r.status,
    r.points
FROM result r
JOIN rider rd
    ON r.rider_id = rd.rider_id
LEFT JOIN club cl
    ON rd.club_id = cl.club_id
JOIN category cat
    ON r.category_id = cat.category_id
JOIN competition c
    ON r.competition_id = c.competition_id
JOIN cup cp
    ON c.cup_id = cp.cup_id
JOIN season s
    ON cp.season_id = s.season_id
ORDER BY
    cat.name,
    r.placement;

-- Count results belonging to a specific import
-- Change result_import_id when required.
SELECT COUNT(*) AS total_results
FROM result
WHERE result_import_id = 1;