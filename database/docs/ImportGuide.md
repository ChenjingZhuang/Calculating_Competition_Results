# Database and Result Import Guide

## Purpose

This document explains how to set up the database environment and use the Excel result importer implemented for the cycling cup system.

It is intended to help other project members continue working with the database and imported result data.

---

# 1. Required Software

The database and result importer use:  
* MySQL Server
* MySQL Workbench
* PHP
* Composer
* PhpSpreadsheet

A code editor such as Visual Studio Code can be used to work with the PHP files.

---

# 2. Install MySQL

Install MySQL Server for your operating system.

MySQL Server is used to run the `cycling_cup_system` database.

After installation, make sure the MySQL server is running.

The local development database uses the default MySQL port:  
```text
3306
```

Keep the MySQL username and password created during installation because they are required by the PHP database connection.

---

# 3. Install MySQL Workbench

Install MySQL Workbench.

Workbench provides a graphical interface for connecting to MySQL and viewing the project database.

After installation:  
1. Open MySQL Workbench.
2. Create or open a local MySQL connection.
3. Use the MySQL username and password configured during MySQL installation.
4. Connect to the local MySQL server.
5. The project database can then be created using the SQL scripts provided in the project.

MySQL Workbench is only the interface used to work with the database. The MySQL Server itself runs separately.

---

# 4. Create the Project Database

The SQL scripts are located in:  
```text
database/sql/
```

The database used by the project is:  
```text
cycling_cup_system
```

Use the SQL scripts in this folder to create the database and the required tables.

The database contains the following 12 application tables:  
```text
season
discipline
cup
competition
category
club
rider
result_import
result
scoring_rule
scoring_rule_detail
point_adjustment
```

The detailed table structures are documented in `TableDesign.md`.

---

# 5. Database SQL Files

The SQL files are stored in:  
```text
database/sql/
```

| File                  | Purpose                                                        |
| --------------------- | -------------------------------------------------------------- |
| `create_database.sql` | Creates the `cycling_cup_system` database.                     |
| `create_tables.sql`   | Creates the 12 application tables and their relationships.     |
| `sample_data.sql`     | Inserts sample development data for checking the database.     |
| `test_queries.sql`    | Contains useful queries for viewing and verifying stored data. |

For a new database setup, run:  
```text
1. create_database.sql
2. create_tables.sql
```

`sample_data.sql` is optional and is intended only for development and testing.

`test_queries.sql` contains useful SELECT queries for checking whether data has been inserted correctly.

The `uploaded_by` value in `sample_data.sql` is a temporary WordPress user ID and may need to be changed to an existing `wp_users.ID` when the database is integrated with WordPress.

---

# 6. Install PHP

PHP is required to run the parser and database import scripts.

After installing PHP, verify the installation from a terminal:  
```bash
php -v
```

If PHP is installed correctly, the command displays the installed PHP version.

---

# 7. Install Composer

Composer is used to manage the PHP dependencies required by the importer.

After installing Composer, verify the installation:  
```bash
composer --version
```

The Composer configuration for the parser is stored in:  
```text
database/parser/composer.json
database/parser/composer.lock
```

---

# 8. Install Project PHP Dependencies

Open a terminal and move into the parser directory:  
```bash
cd database/parser
```

Run:  
```bash
composer install
```

Composer reads `composer.json` and `composer.lock` and installs the required dependencies.

This creates the:  
```text
vendor/
```
directory.

The `vendor/` directory contains automatically installed Composer dependencies and should not be manually edited.

The Excel importer uses PhpSpreadsheet to read Excel files.

---

# 9. Configure the Database Connection

The database connection is handled by:  
```text
DB.php
```

Make sure the connection settings match the local MySQL installation.

The connection requires:  
```text
Host
Database name
Username
Password
```

The database name should be:  
```text
cycling_cup_system
```

The local MySQL server normally uses:  
```text
Host: localhost
Port: 3306
```

The username and password depend on the local MySQL installation.

Do not commit personal database passwords to the shared repository.

---

# 10. Test the Database Connection

The database connection can be checked using:  
```text
TestDatabase.php
```

From the parser directory, run:  
```bash
php TestDatabase.php
```

If the connection is configured correctly, the script should connect to the `cycling_cup_system` database successfully.

---

# 11. Sample Result Files

The original Excel result files provided for the project are stored in:  
```text
database/sample-data/original-excel/
```

These files can be used when running or checking the result parser.

The original files should be kept unchanged so that they remain available as reference input data.

---

# 12. Main Import Files

The main files used by the result importer are:

| File                         | Purpose                                                          |
| ---------------------------- | ---------------------------------------------------------------- |
| `ImportResults.php`          | Coordinates the complete result import process.                  |
| `ParseResults.php`           | Parses the Excel result file into structured result data.        |
| `Validator.php`              | Validates parsed results before database insertion.              |
| `DB.php`                     | Creates the PDO connection to MySQL.                             |
| `CategoryRepository.php`     | Finds an existing category by category name and discipline.      |
| `ClubRepository.php`         | Finds an existing club or creates it when necessary.             |
| `RiderRepository.php`        | Finds an existing rider or creates the rider when necessary.     |
| `ResultImportRepository.php` | Creates and updates result import records.                       |
| `ResultRepository.php`       | Inserts validated competition results into the database.         |
| `ReadExcel.php`              | Reads and inspects Excel result files during parser development. |

---

# 13. How the Import Works

The implemented import process is:

```text
Excel Result File
        ↓
Parse Excel Results
        ↓
Validate Parsed Results
        ↓
Resolve Category
        ↓
Find/Create Club
        ↓
Find/Create Rider
        ↓
Create Result Import
        ↓
Insert Results
        ↓
Complete Result Import
        ↓
MySQL Database
```

`ImportResults.php` coordinates these operations.

---

# 14. Parsed Result Structure

The parser converts each result into the following structure:  
```text
category
placement
last_name
first_name
club
finish_time
status
```

The supported result statuses are: 
```text
Finished
DNS
DNF
DSQ
```

Club information is optional.

Placement and finish time may be empty for DNS, DNF and DSQ results.

Finish times are stored as text in the database so that the formatting from the source result file can be preserved.

---

# 15. Category Handling

Categories are not automatically created during result import.

The category must already exist in the `category` table for the selected discipline.

`CategoryRepository.php` searches for the category using:

```text
category name
+
discipline ID
```

If an imported category cannot be found in the database, the import is stopped.

This prevents a spelling difference or unexpected category name in an Excel file from automatically creating an incorrect category.

---

# 16. Club Handling

Club information comes from the result file when available.

`ClubRepository.php`:

1. Checks whether the club already exists.
2. Returns the existing `club_id` when found.
3. Creates the club when it does not exist.
4. Returns `NULL` when no club information is available.

Club information is therefore optional.

---

# 17. Rider Handling

`RiderRepository.php` checks whether the rider already exists before creating a new rider.

A rider is identified using:  
```text
first name
last name
club
```

If the rider already exists, the existing `rider_id` is used.

If the rider does not exist, a new rider record is created.

Riders without club information are supported because `rider.club_id` can be `NULL`.

---

# 18. Result Import Tracking

Each result file import creates a record in:  
```text
result_import
```

The import record tracks information including:  
* Competition
* File name
* Import status
* Total records
* Imported records
* Failed records
* User responsible for the import

The imported results are linked back to this record through:  
```text
result.result_import_id
```

---

# 19. Result Storage

Validated results are stored in the:  
```text
result
```
table.

Each result is linked to:  
```text
Competition
Rider
Category
Result Import
```

The result also stores:  
```text
Placement
Finish time
Status
Points
```

Points are currently inserted with a value of:  
```text
0
```

The scoring/ranking logic is responsible for calculating the actual points.

---

# 20. Duplicate Import Protection

Before importing results, the importer checks whether results already exist for the selected competition.

If results already exist, the import is stopped.

This prevents the same competition results from being inserted into the database more than once.

---

# 21. Transaction and Rollback

Database insertion is performed inside a transaction.

If the import completes successfully:  
```text
COMMIT
```
is performed and the imported data remains in the database.

If an error occurs:  
```text
ROLLBACK
```
is performed.

This prevents an unsuccessful import from leaving only part of the result file in the database.

---

# 22. Running an Import

Before running an import:  
1. Make sure MySQL Server is running.
2. Make sure the `cycling_cup_system` database and tables exist.
3. Make sure the database connection in `DB.php` is correct.
4. Make sure the required season, discipline, cup, competition and categories exist in the database.
5. Select the Excel result file to import.
6. Set the correct competition and discipline IDs used by the import.

From the parser directory, run:  
```bash
php ImportResults.php
```

The importer will parse and validate the result file and then insert the valid data into the database.

After the import, the results can be viewed in MySQL Workbench from the `result` table.

The import information can be viewed from the `result_import` table.

---

# 23. Development Test Files

The following files were created to check individual parts of the implementation:  
```text
TestDatabase.php
TestCategory.php
TestClub.php
TestRider.php
TestResultImport.php
TestResults.php
```

These files are development test scripts and are not part of the main application import workflow.

They can be used when checking individual repository/database operations.

---

# 24. Important Files and Folders

```text
database/
│
├── docs/
│   └── Database and importer documentation
│
├── parser/
│   ├── ImportResults.php
│   ├── ParseResults.php
│   ├── Validator.php
│   ├── DB.php
│   ├── CategoryRepository.php
│   ├── ClubRepository.php
│   ├── RiderRepository.php
│   ├── ResultImportRepository.php
│   ├── ResultRepository.php
│   ├── ReadExcel.php
│   ├── composer.json
│   ├── composer.lock
│   └── vendor/
│
├── sample-data/
│   └── original-excel/
│       └── Original result files
│
└── sql/
    └── Database creation scripts
```

---

# 25. Handover to the Remaining Application

The database and result import implementation are responsible for converting the provided Excel result files into validated database records.

After a successful import, the remaining application can use the data stored in the `result` table.

The scoring/ranking implementation can use:

```text
result.competition_id
result.rider_id
result.category_id
result.placement
result.finish_time
result.status
result.points
```

The cup and competition relationships can be used to determine which results belong to the required cup.

The scoring logic is responsible for calculating points and standings. The database importer does not calculate the final standings.

The backend integration and application testing are handled separately from the database/import implementation.