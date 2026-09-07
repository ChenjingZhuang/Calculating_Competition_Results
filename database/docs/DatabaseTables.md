# Database Tables

## Purpose

This document lists the tables implemented in the cycling cup system database.

The database contains 12 application tables.

| Table                 | Purpose                                                                       |
| --------------------- | ----------------------------------------------------------------------------- |
| `season`              | Stores cycling cup seasons.                                                   |
| `discipline`          | Stores cycling disciplines.                                                   |
| `cup`                 | Stores cups and their season, discipline, scoring and counted-event settings. |
| `competition`         | Stores individual competitions belonging to a cup.                            |
| `category`            | Stores competition categories associated with a discipline.                   |
| `club`                | Stores cycling clubs or teams.                                                |
| `rider`               | Stores riders and their optional club association.                            |
| `result_import`       | Stores information about imported competition result files.                   |
| `result`              | Stores individual rider results for competitions.                             |
| `scoring_rule`        | Stores scoring rule configurations.                                           |
| `scoring_rule_detail` | Stores placement-to-points values for scoring rules.                          |
| `point_adjustment`    | Stores manual adjustments made to result points.                              |

## Other Data

The following do not require separate application tables:  
* **Cup standings** — calculated from stored results.
* **Tie-breaking** — handled by the ranking logic.
* **Administrator accounts** — handled by WordPress users.
* **Counted-event rules** — stored as cup configuration.
* **Standing exports** — generated when required.