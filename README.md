# Calculating Competition Results Project

## 1. Short Description of the Solution

A WordPress plugin for managing competition seasons, cups, categories, Excel result imports, standings calculation, and CSV export.

This project automates the workflow for:
- creating seasons and cups
- creating competitions and categories
- importing race results from Excel
- calculating standings using points, counting rules, and tie-breakers
- exporting standings to CSV
- adjusting points manually when needed

## 2. Project Members and Roles

- **Chenjing Zhuang** – Frontend Developer
- **Nikolai Podkorytov** - Project Manager &
Backend Developer
- **Katherine Sebastin** - Data Import & Database
Developer
- **Saku Hyvärinen** - Ranking Logic & QA
Engineer

## 3. Technologies

### Backend
- WordPress plugin architecture
- PHP 8+
- MySQL / WordPress database
- custom REST API routes

### Frontend
- WordPress admin pages
- HTML + JavaScript for dashboard and standings UI
- optional public standings shortcode for frontend display

### Libraries
- PhpSpreadsheet for Excel parsing
- custom scoring and standings logic

### Tools

- **Git** – version control
- **GitHub** – code repository and team collaboration
- **Postman / Insomnia** – API testing and debugging
- **Figma** – UI/UX wireframing and design
- **VS Code** – code editor and development environment

### Deployment

The intended experience for a non-technical admin is a normal website opened in a browser. The admin should not need VS Code, a project folder, PHP commands, or Composer. This repository is the software source; it is not itself a public website. A WordPress site must first be installed on a web host and connected to a domain.

#### One-time setup by the project team or site administrator

1. Choose a web hosting provider that supports WordPress, PHP 8.0 or newer, and MySQL. Register a domain name or use a domain the admin already owns. Enable HTTPS and automatic backups.
2. Install WordPress on the hosting account. Many WordPress hosts provide a guided or one-click installation.
3. Package the complete plugin directory as a ZIP file, keeping the directory structure intact. Include `database/parser/vendor/` because the Excel importer loads PhpSpreadsheet from that folder. Do not include the `.git` folder in a release package.
4. Sign in to the WordPress site as an administrator. Open **Plugins > Add New Plugin > Upload Plugin**, upload the ZIP, install it, and activate **Calculating Competition Results**. Activation creates the plugin's database tables.
5. Create an initial season, cup, competition, and categories in the plugin's admin pages.
6. Create a public WordPress page named, for example, **Standings**, add `[competition_results_standings]` to its content, and publish it.
7. Test the full flow on the hosted site: sign in as an administrator, import a sample Excel file, check the calculated standings, and open the public page in a private browser window.

The hosting account's PHP upload-size limit must allow the admin's Excel files. Increase it through the hosting control panel or ask the hosting provider if uploads fail. Keep WordPress, PHP, and the plugin updated, use individual administrator accounts with strong passwords, and confirm that backups can be restored.

#### Admin's normal use

After setup, the admin uses the domain in a browser. They sign in to WordPress to manage competition data and import Excel results. Spectators open the published **Standings** page without signing in. Public API routes provide read-only data; changes require WordPress administrator permissions.

This repository does not provision hosting, register a domain, or publish the site. A project team member or hosting provider must perform the one-time setup and give the admin the site's URL and their WordPress login details.

#### Public Standings Page

The plugin provides a shortcode for publishing cup standings on a normal WordPress page. Once the one-time setup above is complete, the page is available at the site's public URL.

1. In WordPress, create a new page, for example `Standings`.
2. Add the following shortcode to the page content:

   ```text
   [competition_results_standings]
   ```

3. Publish the page and open it in a browser.

### Testing

Run the core calculator regression tests from the project root:

```bash
php tests/test-calculator.php
```

The tests validate scoring, best-N result selection, DNS/DNF/DSQ handling,
manual point overrides, tie-breakers, and shared ranks. Excel import should
also be checked manually in a working WordPress installation.

## 4. Architecture

- `calculating-competition-results.php` – plugin bootstrap and registration
- `includes/class-rest-api.php` – REST endpoints
- `includes/services/class-database.php` – database operations and CRUD logic
- `includes/class-excel-importer.php` – Excel parsing and validation
- `includes/class-points-calculator.php` – cup standings calculation
- `includes/competition-results-calculator.php` – per-rider scoring and ranking logic
- `frontend/admin-dashboard.html` – admin dashboard UI
- `frontend/standings.html` – standings page UI
- `tests/test-calculator.php` – calculator regression tests

## 5. Presentation Layer (Frontend)

The presentation layer consists of WordPress admin pages and a public standings view.

- **Admin dashboard:** manages seasons, cups, competitions, categories, Excel imports, and manual result-point corrections.
- **Standings page:** displays cup standings and supports CSV export.
- **Public shortcode:** `[competition_results_standings]` renders a selectable public standings table on any WordPress page.

The frontend uses HTML and JavaScript. It requests data from the custom WordPress REST API and renders the returned JSON without duplicating scoring logic in the browser.

## 6. Application Layer (Backend API)

Base path:

```text
/wp-json/competition/v1
```

Main endpoints:
- `GET /seasons`
- `POST /seasons`
- `DELETE /seasons/{id}`

- `GET /cups?season_id={season_id}`
- `POST /cups`
- `DELETE /cups/{id}`

- `GET /competitions?cup_id={cup_id}`
- `POST /competitions`
- `DELETE /competitions/{id}`

- `GET /categories?discipline_id={discipline_id}`
- `POST /categories`
- `DELETE /categories/{id}`

- `GET /results?competition_id={competition_id}`
- `POST /results/{id}/adjustment`

- `GET /standings?cup_id={cup_id}`
- `GET /standings?cup_id={cup_id}&category={category}`
- `GET /standings/export?cup_id={cup_id}`
- `GET /standings/export?cup_id={cup_id}&category={category}`

- `GET /athlete/{name}?cup_id={cup_id}`

These routes are used by the admin dashboard and the public standings UI.
Read endpoints are public; routes that create, delete, or adjust data require
the WordPress `manage_options` capability.

## 7. Data Layer (Database)

- **Type:** MySQL database managed through WordPress `$wpdb`
- **Table prefix:** WordPress runtime prefix (`$wpdb->prefix`)
- **Database service:** `includes/services/class-database.php`

### Main Tables

- `season` - competition seasons
- `discipline` - sport disciplines
- `cup` - competition series belonging to a season
- `competition` - individual events belonging to a cup
- `category` - competition categories belonging to a discipline
- `club` - rider clubs
- `rider` - competitors and their club associations
- `scoring_rule` and `scoring_rule_detail` - configurable points tables
- `result_import` - Excel import history and import status
- `result` - individual rider results for each competition
- `point_adjustment` - manual point-correction audit records

### Relationships

A season contains cups. Each cup belongs to one discipline and scoring rule, and contains individual competitions. Results connect a competition, rider, category, and import record. Riders may belong to a club. Manual point adjustments belong to a specific result.

```text
Season -> Cup -> Competition -> Result
Discipline -> Category
Discipline -> Cup
Scoring rule -> Cup
Rider -> Result
Club -> Rider
Result import -> Result
Result -> Point adjustment
```

### Scoring logic summary

The standings engine uses a shared calculation model:
- points assigned by placement from a scoring table
- best-N counted results rule
- placements from 16th upwards count as 1 point
- DNS / DNF / DSQ are treated as non-scoring results
- equal scores are resolved by head-to-head wins and then place counts in order
- manual override adjustments can change final scoring

The logic is validated by `tests/test-calculator.php`.

## 8. Flow Overview

1. An administrator creates a season and cup.
2. The administrator creates cup competitions and categories.
3. An Excel results file is uploaded for a selected competition.
4. The importer validates and normalizes rows, then stores results in the WordPress database.
5. The standings calculator applies the scoring table, best-N rule, manual overrides, and tie-break rules.
6. The admin or public standings UI requests the calculated standings through the REST API.
7. The user can download standings as a CSV file.

## 9. Functionalities

### Key Features

#### Season and cup management
Administrators can create and manage:
- seasons
- cups
- competitions
- categories

#### Excel import workflow
The importer reads Excel files and maps rows into competition results. It includes validation for:
- valid placement values
- DNS / DNF / DSQ handling
- category matching
- result normalization
- import status tracking

#### Standings calculation
The app calculates rider totals using:
- scoring table values
- counted event limit / best-N logic
- place counts for tie-breaks
- total score ranking
- manual point adjustments

#### Data correction
Administrators can manually adjust points for specific results and store audit data for transparency.

#### CSV export
Standings can be exported as a CSV file suitable for Excel.

### User Flows

#### Create data in WordPress
1. Create a season.
2. Create a cup under that season.
3. Create a competition.
4. Add categories.

#### Import results
1. Open the admin dashboard.
2. Load the result import flow.
3. Upload an Excel file with valid rider result data.
4. Review imported rows and validation results.

#### View standings
Use the standings page or the public shortcode:

```text
[competition_results_standings]
```

This loads the standings for the selected cup and category.

#### Export standings
Use the CSV export button in the standings UI to download results.

### MVP-Level Functionalities

Current MVP status:
- season management: implemented
- cup management: implemented
- competition management: implemented
- category management: implemented
- Excel import: implemented
- result validation: implemented
- standings calculation: implemented
- manual point adjustments: implemented
- CSV export: implemented
- public standings shortcode: implemented
- test coverage for calculator logic: implemented
