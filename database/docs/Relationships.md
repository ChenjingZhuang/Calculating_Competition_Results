# Entity Relationships

## Purpose

This document describes the main relationships between the database entities.

| Entity A      | Relationship      | Entity B            | Description                                                                               |
| ------------- | ----------------- | ------------------- | ----------------------------------------------------------------------------------------- |
| Season        | One-to-Many (1:N) | Cup                 | A season can contain multiple cups. Each cup belongs to one season.                       |
| Discipline    | One-to-Many (1:N) | Category                 | A discipline can contain multiple categories. Each category belongs to one discipline.               |
| Cup           | One-to-Many (1:N) | Competition         | A cup can contain multiple competitions. Each competition belongs to one cup.             |
| Cup           | One-to-Many (1:N) | Category            | A cup can contain multiple categories. Each category belongs to one cup.                  |
| Scoring Rule           | One-to-Many (1:N) | Cup        | A scoring rule can be used by multiple cups. Each cup references one scoring rule.                       |
| Scoring Rule  | One-to-Many (1:N) | Scoring Rule Detail | A scoring rule contains placement and point values.                                       |
| Competition   | One-to-Many (1:N) | Result Import       | A competition can have result imports. Each import belongs to one competition.            |
| Competition   | One-to-Many (1:N) | Result              | A competition can contain many results. Each result belongs to one competition.           |
| Category      | One-to-Many (1:N) | Result              | A category can contain many results. Each result belongs to one category.                 |
| Club          | One-to-Many (1:N) | Rider               | A club can have many riders. A rider may belong to a club.                                |
| Rider         | One-to-Many (1:N) | Result              | A rider can have results from multiple competitions. Each result belongs to one rider.    |
| Result Import | One-to-Many (1:N) | Result              | One result import can contain many results. Each imported result is linked to its import. |
| Result        | One-to-Many (1:N) | Point Adjustment    | A result can have multiple point adjustments. Each adjustment belongs to one result.      |

## WordPress Users

Administrator accounts are handled by WordPress rather than a separate database table.

The `uploaded_by` and adjustment user references are used to identify the WordPress user responsible for those actions.