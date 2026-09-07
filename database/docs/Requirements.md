# Requirements

This document describes the main database and data import requirements for the cycling cup calculation system.

## Requirements

The database must support:  
* Multiple seasons, disciplines, cups, competitions, and categories.
* Riders and their clubs. A rider's club is optional.
* Competition results for each rider and category.
* Result placements, finish times and statuses.
* DNS, DNF and DSQ results.
* Scoring rules and points.
* Manual point adjustments.
* Tracking of imported result files.
* Multiple competitions contributing to cup standings.

## Excel Import Requirements

The result importer must:  
* Read Excel result files using PhpSpreadsheet.
* Detect categories from the result file.
* Extract:  
  * Category
  * Placement
  * Last name
  * First name
  * Club, when available
  * Finish time
  * Result status
* Support finished results and DNS, DNF and DSQ.
* Validate parsed results before inserting them into the database.
* Find or create clubs and riders.
* Match imported categories with existing database categories.
* Store imported results in the database.
* Track each file import using `result_import`.
* Prevent duplicate results for the same competition.
* Roll back the database import if the import cannot be completed successfully.

## Main Database Tables

The system uses 12 tables:  
1. `season`
2. `discipline`
3. `cup`
4. `competition`
5. `category`
6. `club`
7. `rider`
8. `result_import`
9. `result`
10. `scoring_rule`
11. `scoring_rule_detail`
12. `point_adjustment`

Detailed table structures and relationships are documented separately in the database documentation.