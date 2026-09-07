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
        'Import Results',
        'Import Results',
        'manage_options',
        'competition-import',
        'competition_results_render_import'
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
 * Initialize plugin
 */
function competition_results_init() {
    // Load dependencies
    competition_results_load_dependencies();
    
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
