# Database Table Design

## Purpose

This document describes the final structure of the 12 database tables used by the cycling cup system.

## Season

| Column       | Type                       | Key    | Nullable / Default         |
| ------------ | -------------------------- | ------ | -------------------------- |
| `season_id`  | INT AUTO_INCREMENT         | PK     | No                         |
| `year`       | INT                        | UNIQUE | No                         |
| `status`     | ENUM('Active', 'Archived') | —      | Default: `Active`          |
| `created_at` | DATETIME                   | —      | Default: CURRENT_TIMESTAMP |
| `updated_at` | DATETIME                   | —      | Default: CURRENT_TIMESTAMP |

## Discipline

| Column          | Type               | Key    | Nullable / Default         |
| --------------- | ------------------ | ------ | -------------------------- |
| `discipline_id` | INT AUTO_INCREMENT | PK     | No                         |
| `name`          | VARCHAR(100)       | UNIQUE | No                         |
| `created_at`    | DATETIME           | —      | Default: CURRENT_TIMESTAMP |
| `updated_at`    | DATETIME           | —      | Default: CURRENT_TIMESTAMP |

## Scoring Rule

| Column            | Type               | Key    | Nullable / Default         |
| ----------------- | ------------------ | ------ | -------------------------- |
| `scoring_rule_id` | INT AUTO_INCREMENT | PK     | No                         |
| `name`            | VARCHAR(100)       | UNIQUE | No                         |
| `description`     | TEXT               | —      | NULL                       |
| `created_at`      | DATETIME           | —      | Default: CURRENT_TIMESTAMP |
| `updated_at`      | DATETIME           | —      | Default: CURRENT_TIMESTAMP |

## Cup

| Column            | Type                                   | Key                                 | Nullable / Default         |
| ----------------- | -------------------------------------- | ----------------------------------- | -------------------------- |
| `cup_id`          | INT AUTO_INCREMENT                     | PK                                  | No                         |
| `season_id`       | INT                                    | FK → `season.season_id`             | No                         |
| `discipline_id`   | INT                                    | FK → `discipline.discipline_id`     | No                         |
| `name`            | VARCHAR(100)                           | —                                   | No                         |
| `scoring_rule_id` | INT                                    | FK → `scoring_rule.scoring_rule_id` | No                         |
| `counted_events`  | INT                                    | —                                   | No                         |
| `status`          | ENUM('Draft', 'Published', 'Archived') | —                                   | Default: `Draft`           |
| `created_at`      | DATETIME                               | —                                   | Default: CURRENT_TIMESTAMP |
| `updated_at`      | DATETIME                               | —                                   | Default: CURRENT_TIMESTAMP |

## Competition

| Column           | Type                                       | Key               | Nullable / Default         |
| ---------------- | ------------------------------------------ | ----------------- | -------------------------- |
| `competition_id` | INT AUTO_INCREMENT                         | PK                | No                         |
| `cup_id`         | INT                                        | FK → `cup.cup_id` | No                         |
| `name`           | VARCHAR(150)                               | —                 | No                         |
| `event_date`     | DATE                                       | —                 | No                         |
| `location`       | VARCHAR(150)                               | —                 | NULL                       |
| `result_link`    | VARCHAR(255)                               | —                 | NULL                       |
| `status`         | ENUM('Upcoming', 'Completed', 'Cancelled') | —                 | Default: `Upcoming`        |
| `created_at`     | DATETIME                                   | —                 | Default: CURRENT_TIMESTAMP |
| `updated_at`     | DATETIME                                   | —                 | Default: CURRENT_TIMESTAMP |

## Category

| Column          | Type               | Key                             | Nullable / Default         |
| --------------- | ------------------ | ------------------------------- | -------------------------- |
| `category_id`   | INT AUTO_INCREMENT | PK                              | No                         |
| `discipline_id` | INT                | FK → `discipline.discipline_id` | No                         |
| `name`          | VARCHAR(100)       | —                               | No                         |
| `description`   | TEXT               | —                               | NULL                       |
| `created_at`    | DATETIME           | —                               | Default: CURRENT_TIMESTAMP |
| `updated_at`    | DATETIME           | —                               | Default: CURRENT_TIMESTAMP |

The combination of `discipline_id` and `name` must be unique.

## Club

| Column         | Type               | Key    | Nullable / Default         |
| -------------- | ------------------ | ------ | -------------------------- |
| `club_id`      | INT AUTO_INCREMENT | PK     | No                         |
| `name`         | VARCHAR(150)       | UNIQUE | No                         |
| `abbreviation` | VARCHAR(20)        | —      | NULL                       |
| `location`     | VARCHAR(100)       | —      | NULL                       |
| `created_at`   | DATETIME           | —      | Default: CURRENT_TIMESTAMP |
| `updated_at`   | DATETIME           | —      | Default: CURRENT_TIMESTAMP |

## Rider

| Column       | Type               | Key                 | Nullable / Default         |
| ------------ | ------------------ | ------------------- | -------------------------- |
| `rider_id`   | INT AUTO_INCREMENT | PK                  | No                         |
| `club_id`    | INT                | FK → `club.club_id` | NULL                       |
| `first_name` | VARCHAR(100)       | —                   | No                         |
| `last_name`  | VARCHAR(100)       | —                   | No                         |
| `created_at` | DATETIME           | —                   | Default: CURRENT_TIMESTAMP |
| `updated_at` | DATETIME           | —                   | Default: CURRENT_TIMESTAMP |

`club_id` is optional because club information is not always available in imported result files.

## Result Import

| Column             | Type                                   | Key                               | Nullable / Default         |
| ------------------ | -------------------------------------- | --------------------------------- | -------------------------- |
| `result_import_id` | INT AUTO_INCREMENT                     | PK                                | No                         |
| `competition_id`   | INT                                    | FK → `competition.competition_id` | No                         |
| `file_name`        | VARCHAR(255)                           | —                                 | No                         |
| `upload_date`      | DATETIME                               | —                                 | Default: CURRENT_TIMESTAMP |
| `import_status`    | ENUM('Pending', 'Completed', 'Failed') | —                                 | Default: `Pending`         |
| `total_records`    | INT                                    | —                                 | Default: 0                 |
| `imported_records` | INT                                    | —                                 | Default: 0                 |
| `failed_records`   | INT                                    | —                                 | Default: 0                 |
| `error_message`    | TEXT                                   | —                                 | NULL                       |
| `uploaded_by`      | BIGINT UNSIGNED                        | WordPress user reference          | No                         |
| `created_at`       | DATETIME                               | —                                 | Default: CURRENT_TIMESTAMP |
| `updated_at`       | DATETIME                               | —                                 | Default: CURRENT_TIMESTAMP |

`uploaded_by` refers to `wp_users.ID`.

## Result

| Column             | Type                                  | Key                                   | Nullable / Default         |
| ------------------ | ------------------------------------- | ------------------------------------- | -------------------------- |
| `result_id`        | INT AUTO_INCREMENT                    | PK                                    | No                         |
| `competition_id`   | INT                                   | FK → `competition.competition_id`     | No                         |
| `rider_id`         | INT                                   | FK → `rider.rider_id`                 | No                         |
| `category_id`      | INT                                   | FK → `category.category_id`           | No                         |
| `result_import_id` | INT                                   | FK → `result_import.result_import_id` | No                         |
| `placement`        | INT                                   | —                                     | NULL                       |
| `finish_time`      | VARCHAR(30)                           | —                                     | NULL                       |
| `status`           | ENUM('Finished', 'DNS', 'DNF', 'DSQ') | —                                     | Default: `Finished`        |
| `points`           | INT                                   | —                                     | Default: 0                 |
| `created_at`       | DATETIME                              | —                                     | Default: CURRENT_TIMESTAMP |
| `updated_at`       | DATETIME                              | —                                     | Default: CURRENT_TIMESTAMP |

`placement` and `finish_time` may be NULL for DNS, DNF and DSQ results.

The combination of `competition_id`, `rider_id` and `category_id` must be unique.

## Scoring Rule Detail

| Column                   | Type               | Key                                 | Nullable / Default         |
| ------------------------ | ------------------ | ----------------------------------- | -------------------------- |
| `scoring_rule_detail_id` | INT AUTO_INCREMENT | PK                                  | No                         |
| `scoring_rule_id`        | INT                | FK → `scoring_rule.scoring_rule_id` | No                         |
| `placement`              | INT                | —                                   | No                         |
| `points`                 | INT                | —                                   | Default: 0                 |
| `created_at`             | DATETIME           | —                                   | Default: CURRENT_TIMESTAMP |
| `updated_at`             | DATETIME           | —                                   | Default: CURRENT_TIMESTAMP |

The combination of `scoring_rule_id` and `placement` must be unique.

## Point Adjustment

| Column                | Type               | Key                      | Nullable / Default         |
| --------------------- | ------------------ | ------------------------ | -------------------------- |
| `point_adjustment_id` | INT AUTO_INCREMENT | PK                       | No                         |
| `result_id`           | INT                | FK → `result.result_id`  | No                         |
| `original_points`     | INT                | —                        | No                         |
| `adjusted_points`     | INT                | —                        | No                         |
| `adjustment_reason`   | TEXT               | —                        | No                         |
| `adjusted_by`         | BIGINT UNSIGNED    | WordPress user reference | No                         |
| `adjustment_date`     | DATETIME           | —                        | Default: CURRENT_TIMESTAMP |
| `created_at`          | DATETIME           | —                        | Default: CURRENT_TIMESTAMP |
| `updated_at`          | DATETIME           | —                        | Default: CURRENT_TIMESTAMP |

`adjusted_by` refers to `wp_users.ID`.