# Calculating_Competition_Results Project plan

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

- **Chenjing Zhuang** –
- **Nikolai Podkorytov** -
- **Katherine Sebastin** -
- **Saku Hyvärinen** -

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

1. Place the plugin folder in the WordPress installation under `wp-content/plugins/`.
2. Activate the plugin in the WordPress admin area.
3. Ensure the WordPress database is active and accessible.
4. Activate the plugin to create required tables through the plugin activation hook.

#### Public Standings Page

The plugin provides a shortcode for publishing cup standings on a normal WordPress page.

1. Activate the plugin from the WordPress **Plugins** page.
2. Create at least one active season, cup, competition, and import results through the Competition Results admin pages.
3. In WordPress, create a new page, for example `Standings`.
4. Add the following shortcode to the page content:

   ```text
   [competition_results_standings]
   ```

5. Publish the page and open it in a browser.

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

## 10. Resourcing

### Each Member Job Overview and Estimated Time

| Member Name | Role | Main Responsibilities | Estimated Hours |
| --- | --- | --- | --- |
| Chenjing |  |  |  |
| Nikolai |  |  |  |
| Katherine |  |  |  |
| Saku |  |  |  |

## 11. Timeline

[Provide a week-by-week or phase-by-phase schedule]

