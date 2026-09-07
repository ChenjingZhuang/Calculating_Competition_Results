# Database Entities

## Purpose

This document briefly describes the entities implemented in the cycling cup system database.

## Entities

| Entity              | Description                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------------------------- |
| Season              | Represents a cycling cup season, such as 2026.                                                                |
| Discipline          | Represents a cycling discipline, such as MTB, Road Cycling or Cyclocross.                                     |
| Cup                 | Represents a cup within a season and discipline. Stores cup settings such as the number of counted events.    |
| Competition         | Represents an individual competition belonging to a cup.                                                      |
| Category            | Represents a competition category or series, such as M Elite, N Elite or MU19.                                |
| Club                | Represents a rider's cycling club or team.                                                                    |
| Rider               | Represents a rider participating in competitions. Stores the rider's first name, last name and optional club. |
| Result Import       | Represents an imported result file and stores information about the import process.                           |
| Result              | Represents a rider's result in a competition, including category, placement, finish time, status and points.  |
| Scoring Rule        | Represents the scoring configuration used for a cup.                                                          |
| Scoring Rule Detail | Stores the points assigned to individual placements within a scoring rule.                                    |
| Point Adjustment    | Stores manual point adjustments made to a result.                                                             |

## Entities Not Stored as Separate Tables

The following are handled by application logic rather than separate database tables:  
* Cup standings
* Tie-breaking
* Dropped-event calculations
* Administrator accounts
* Standing exports

Administrator accounts are handled through WordPress users.