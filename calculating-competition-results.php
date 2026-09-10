<?php
/**
 * Plugin Name: Calculating Competition Results
 * Plugin URI: https://github.com/ChenjingZhuang/Calculating_Competition_Results
 * Description: Automates competition results calculation from Excel files
 * Version: 1.0.0
 * Author: Your Team
 * Author URI: https://github.com/ChenjingZhuang/Calculating_Competition_Results
 * License: GPL-2.0-or-later
 * Text Domain: competition-results
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'COMPETITION_RESULTS_VERSION', '1.0.0' );
define( 'COMPETITION_RESULTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COMPETITION_RESULTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load dependencies
 */
function competition_results_load_dependencies() {
    // Load calculator
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/competition-results-calculator.php';
    
    // Load Excel importer
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/class-excel-importer.php';
    
    // Load points calculator
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/class-points-calculator.php';
    
    // Load database service
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/services/class-database.php';
    
    // Load admin
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/admin/class-admin.php';
    
    // Load REST API
    require_once COMPETITION_RESULTS_PLUGIN_DIR . 'includes/class-rest-api.php';
}

/**
 * Activation hook
 */
function competition_results_activate() {
    competition_results_load_dependencies();

    $db = new Competition_Database();
    $db->create_tables();
    
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
function competition_results_deactivate() {
    flush_rewrite_rules();
}

/**
 * Add admin menu
 */
function competition_results_add_admin_menu() {
    add_menu_page(
        'Competition Results',
        'Competition Results',
        'manage_options',
        'competition-results',
        'competition_results_render_dashboard',
        'dashicons-chart-line',
        30
    );
    
    add_submenu_page(
        'competition-results',
        'Add Season',
        'Add Season',
        'manage_options',
        'competition-season',
        'competition_results_render_season_form'
    );

    add_submenu_page(
        'competition-results',
        'Add Cup',
        'Add Cup',
        'manage_options',
        'competition-cup',
        'competition_results_render_cup_form'
    );

    add_submenu_page(
        'competition-results',
        'Add Competition',
        'Add Competition',
        'manage_options',
        'competition-competition',
        'competition_results_render_competition_form'
    );

    add_submenu_page(
        'competition-results',
        'Categories',
        'Categories',
        'manage_options',
        'competition-categories',
        'competition_results_render_category_form'
    );

    add_submenu_page(
        'competition-results',
        'Import Results',
        'Import Results',
        'manage_options',
        'competition-import',
        'competition_results_render_dashboard'
    );
    
    add_submenu_page(
        'competition-results',
        'Standings',
        'Standings',
        'manage_options',
        'competition-standings',
        'competition_results_render_standings'
    );
}

/**
 * Enqueue admin assets
 *
 * @param string $hook Current admin page hook.
 */
function competition_results_enqueue_admin_assets( string $hook ) {
    if ( strpos( $hook, 'competition-' ) === false ) {
        return;
    }
    
    wp_enqueue_style(
        'competition-admin',
        COMPETITION_RESULTS_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        COMPETITION_RESULTS_VERSION
    );
    
    wp_enqueue_script(
        'competition-admin',
        COMPETITION_RESULTS_PLUGIN_URL . 'assets/js/admin.js',
        array( 'jquery' ),
        COMPETITION_RESULTS_VERSION,
        true
    );
    
    wp_localize_script( 'competition-admin', 'competitionAdmin', array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'restUrl'   => esc_url_raw( untrailingslashit( rest_url( 'competition/v1' ) ) ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
        'nonce'     => wp_create_nonce( 'competition_admin_nonce' )
    ));
}

/**
 * Render dashboard
 */
function competition_results_render_dashboard() {
    $competition_admin = array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'restUrl'   => esc_url_raw( untrailingslashit( rest_url( 'competition/v1' ) ) ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
        'nonce'     => wp_create_nonce( 'competition_admin_nonce' ),
    );
    echo '<script>window.competitionAdmin = ' . wp_json_encode( $competition_admin ) . ';</script>';
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/admin-dashboard.html';
}

/**
 * Render the season form.
 */
function competition_results_render_season_form() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/season-form.html';
}

/**
 * Render the cup form.
 */
function competition_results_render_cup_form() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/cup-form.html';
}

/**
 * Render the competition form.
 */
function competition_results_render_competition_form() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/competition-form.html';
}

/**
 * Render category management.
 */
function competition_results_render_category_form() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/category-form.html';
}

/**
 * Render import page
 */
function competition_results_render_import() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'includes/admin/views/import.php';
}

/**
 * Render standings page
 */
function competition_results_render_standings() {
    include COMPETITION_RESULTS_PLUGIN_DIR . 'frontend/standings.html';
}

/**
 * Render a public standings shortcode as a fallback MVP frontend.
 *
 * @return string
 */
function competition_results_render_public_standings_shortcode() {
    $rest_url = esc_url_raw( untrailingslashit( rest_url( 'competition/v1' ) ) );
    $rest_nonce = wp_create_nonce( 'wp_rest' );

    ob_start();
    ?>
    <div class="competition-results-public-standings">
        <h2>Competition Standings</h2>
        <p id="competition-results-status">Loading competitions...</p>
        <div style="margin-bottom: 12px;">
            <label for="competition-results-cup-select">Cup</label>
            <select id="competition-results-cup-select" style="margin: 0 10px;"></select>
            <label for="competition-results-category-input">Category</label>
            <input id="competition-results-category-input" type="text" placeholder="All categories" style="margin: 0 10px;">
            <button id="competition-results-load" type="button">Load standings</button>
            <button id="competition-results-download" type="button">Download CSV</button>
        </div>
        <div id="competition-results-content"></div>
    </div>
    <script>
        (function () {
            const restUrl = <?php echo wp_json_encode( $rest_url ); ?>;
            const restNonce = <?php echo wp_json_encode( $rest_nonce ); ?>;
            const cupSelect = document.getElementById('competition-results-cup-select');
            const categoryInput = document.getElementById('competition-results-category-input');
            const status = document.getElementById('competition-results-status');
            const content = document.getElementById('competition-results-content');
            const apiBase = restUrl || '/wp-json/competition/v1';
            const requestHeaders = restNonce ? { 'X-WP-Nonce': restNonce } : {};

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>\"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
                });
            }

            function renderStandings(payload) {
                const rows = payload.data && Array.isArray(payload.data.standings) ? payload.data.standings : [];
                if (!rows.length) {
                    content.innerHTML = '<p>No standings found.</p>';
                    return;
                }

                content.innerHTML = '<table style="width:100%; border-collapse: collapse; border: 1px solid #ccc; margin-top: 16px;">' +
                    '<thead><tr><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Rank</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Category</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Athlete</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Club</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">1st</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">2nd</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">3rd</th><th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Total</th></tr></thead><tbody>' +
                    rows.map(function (row) {
                        const counts = row.place_counts || {};
                        return '<tr><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(row.rank) + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(row.category || '') + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:left;">' + escapeHtml(row.athlete_name) + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:left;">' + escapeHtml(row.club || '') + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(counts[1] || 0) + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(counts[2] || 0) + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(counts[3] || 0) + '</td><td style="border: 1px solid #ccc; padding: 8px; text-align:center;">' + escapeHtml(row.total_score) + '</td></tr>';
                    }).join('') + '</tbody></table>';
            }

            function loadStandings() {
                const cupId = cupSelect.value;
                if (!cupId) return;
                status.textContent = 'Loading standings...';
                let url = apiBase + '/standings?cup_id=' + encodeURIComponent(cupId);
                if (categoryInput.value.trim()) {
                    url += '&category=' + encodeURIComponent(categoryInput.value.trim());
                }

                fetch(url, { headers: requestHeaders })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Standings request failed.');
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload.success === false) throw new Error(payload.message || 'Standings request failed.');
                        renderStandings(payload);
                        status.textContent = 'Standings available';
                    })
                    .catch(function (error) {
                        status.textContent = error.message;
                        content.innerHTML = '';
                    });
            }

            fetch(apiBase + '/seasons', { headers: requestHeaders })
                .then(function (response) {
                    if (!response.ok) throw new Error('Could not load seasons.');
                    return response.json();
                })
                .then(function (seasonPayload) {
                    const seasons = seasonPayload.data || [];
                    if (!seasons.length) throw new Error('No seasons found.');
                    return Promise.all(seasons.map(function (season) {
                        return fetch(apiBase + '/cups?season_id=' + encodeURIComponent(season.id), { headers: requestHeaders }).then(function (response) { return response.json(); });
                    }));
                })
                .then(function (cupPayloads) {
                    const cups = cupPayloads.reduce(function (all, payload) { return all.concat(payload.data || []); }, []);
                    if (!cups.length) {
                        status.textContent = 'No cups found.';
                        return;
                    }
                    cupSelect.innerHTML = cups.map(function (cup) { return '<option value="' + escapeHtml(cup.id) + '">' + escapeHtml(cup.name) + '</option>'; }).join('');
                    loadStandings();
                })
                .catch(function (error) {
                    status.textContent = error.message;
                });

            document.getElementById('competition-results-load').addEventListener('click', loadStandings);
            document.getElementById('competition-results-download').addEventListener('click', function () {
                const cupId = cupSelect.value;
                if (!cupId) return;
                let url = apiBase + '/standings/export?cup_id=' + encodeURIComponent(cupId);
                if (categoryInput.value.trim()) {
                    url += '&category=' + encodeURIComponent(categoryInput.value.trim());
                }
                window.location.href = url;
            });
        }());
    </script>
    <?php
    return ob_get_clean();
}

function competition_results_register_shortcodes() {
    add_shortcode( 'competition_results_standings', 'competition_results_render_public_standings_shortcode' );
}

/**
 * Initialize plugin
 */
function competition_results_init() {
    // Load dependencies
    competition_results_load_dependencies();
    
    // Register frontend shortcodes
    add_action( 'init', 'competition_results_register_shortcodes' );
    
    // Add admin menu
    add_action( 'admin_menu', 'competition_results_add_admin_menu' );
    
    // Enqueue assets
    add_action( 'admin_enqueue_scripts', 'competition_results_enqueue_admin_assets' );
    
    // Initialize admin
    new Competition_Admin();
    
    // Initialize REST API
    new Competition_REST_API();
}

// Register activation/deactivation hooks
register_activation_hook( __FILE__, 'competition_results_activate' );
register_deactivation_hook( __FILE__, 'competition_results_deactivate' );

// Initialize plugin
add_action( 'plugins_loaded', 'competition_results_init' );
