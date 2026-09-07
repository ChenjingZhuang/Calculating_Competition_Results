# Foreign Key Relationships

This document lists the foreign key relationships used in the database.

| Child Table         | Foreign Key        | Parent Table  |
| ------------------- | ------------------ | ------------- |
| Cup                 | `season_id`        | Season        |
| Cup                 | `discipline_id`    | Discipline    |
| Cup                 | `scoring_rule_id`  | Scoring Rule  |
| Competition         | `cup_id`           | Cup           |
| Category            | `discipline_id`    | Discipline    |
| Rider               | `club_id`          | Club          |
| Result              | `competition_id`   | Competition   |
| Result              | `rider_id`         | Rider         |
| Result              | `category_id`      | Category      |
| Result              | `result_import_id` | Result Import |
| Scoring Rule Detail | `scoring_rule_id`  | Scoring Rule  |
| Result Import       | `competition_id`   | Competition   |
| Point Adjustment    | `result_id`        | Result        |

## WordPress User References

| Field                          | References    |
| ------------------------------ | ------------- |
| `result_import.uploaded_by`    | `wp_users.ID` |
| `point_adjustment.adjusted_by` | `wp_users.ID` |

`rider.club_id` is nullable because club information is optional.