# Data Analysis

## Purpose

This document describes the data handled by the Excel result importer and how it is stored for use by the rest of the system.

## Data from Result Files

The importer processes the following result data:  
* Category
* Placement
* Last name
* First name
* Club/team, when available
* Finish time
* Result status

The supported result statuses are:  
* `Finished`
* `DNS`
* `DNF`
* `DSQ`

Club/team is optional. Placement and finish time may be empty for results such as DNS, DNF and DSQ.

## Data Managed by the System

The following information is managed separately in the system and is not created automatically from the result file:  
* Seasons
* Disciplines
* Cups
* Competitions
* Categories
* Scoring rules
* Number of counted events

During import, categories from the result file are matched with categories that already exist in the database.

Clubs and riders are found in the database or created when they do not already exist.

## Imported Data

Each uploaded result file creates a `result_import` record.

Validated competition results are stored in the `result` table and linked to:  
* Competition
* Rider
* Category
* Result import

The stored result data is then available for the scoring and standings calculation.

## Data Flow

```text
Excel Result File
        ↓
Parsing
        ↓
Validation
        ↓
Club / Rider / Category Resolution
        ↓
Database
        ↓
Scoring and Standings
```