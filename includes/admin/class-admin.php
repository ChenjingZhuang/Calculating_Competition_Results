<?php
/**
 * Admin Class
 * Handles admin functionality
 *
 * @package Calculating_Competition_Results
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Competition_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action( 'wp_ajax_competition_import_results', array( $this, 'ajax_import_results' ) );
        add_action( 'wp_ajax_competition_update_result', array( $this, 'ajax_update_result' ) );
        add_action( 'wp_ajax_competition_delete_result', array( $this, 'ajax_delete_result' ) );
        add_action( 'wp_ajax_competition_preview_import', array( $this, 'ajax_preview_import' ) );
    }
    
    /**
     * AJAX: Preview import
     */
    public function ajax_preview_import() {
        check_ajax_referer( 'competition_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }
        
        if ( empty( $_FILES['excel_file'] ) ) {
            wp_send_json_error( array( 'message' => 'No file uploaded' ) );
        }
        
        $file = $_FILES['excel_file'];
        
        // Preview import
        $importer = new Competition_Excel_Importer();
        $result = $importer->preview( $file['tmp_name'] );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * AJAX: Import results
     */
    public function ajax_import_results() {
        if ( ! check_ajax_referer( 'competition_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'The admin session has expired. Reload the page and try again.' ), 403 );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }
        
        if ( empty( $_FILES['excel_file'] ) ) {
            wp_send_json_error( array( 'message' => 'No file uploaded' ) );
        }
        
        $file = $_FILES['excel_file'];
        if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
            $upload_errors = array(
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL    => 'The file upload was interrupted.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is missing.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
                UPLOAD_ERR_EXTENSION => 'A server extension stopped the file upload.',
            );
            wp_send_json_error( array( 'message' => $upload_errors[ (int) $file['error'] ] ?? 'The file upload failed.' ) );
        }
        $competition_id = intval( $_POST['competition_id'] ?? 0 );
        
        if ( $competition_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid competition ID' ) );
        }
        
        // Import Excel
        $importer = new Competition_Excel_Importer();
        $result = $importer->import( $file['tmp_name'], $competition_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        // Save to database
        $db = new Competition_Database();
        $inserted = $db->save_results(
            $result['results'],
            array(
                'competition_id' => $competition_id,
                'file_name' => $file['name'] ?? 'WordPress upload',
                'failed_records' => count( $result['errors'] ?? array() ),
            )
        );

        if ( is_wp_error( $inserted ) ) {
            wp_send_json_error( array( 'message' => $inserted->get_error_message() ) );
        }
        
        wp_send_json_success( array(
            'message' => sprintf( 'Imported %d results', $inserted ),
            'count' => $inserted,
            'errors' => $result['errors']
        ));
    }
    
    /**
     * AJAX: Update result
     */
    public function ajax_update_result() {
        check_ajax_referer( 'competition_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }
        
        $result_id = intval( $_POST['result_id'] ?? 0 );
        
        if ( $result_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid result ID' ) );
        }
        
        $data = array(
            'athlete_name' => sanitize_text_field( $_POST['athlete_name'] ?? '' ),
            'category' => sanitize_text_field( $_POST['category'] ?? '' ),
            'placement' => intval( $_POST['placement'] ?? 0 ),
            'points' => floatval( $_POST['points'] ?? 0 ),
            'status' => sanitize_text_field( $_POST['status'] ?? 'finished' )
        );
        
        $db = new Competition_Database();
        $updated = $db->update_result( $result_id, $data );
        
        if ( $updated === false ) {
            wp_send_json_error( array( 'message' => 'Update failed' ) );
        }
        
        wp_send_json_success( array( 'message' => 'Result updated' ) );
    }
    
    /**
     * AJAX: Delete result
     */
    public function ajax_delete_result() {
        check_ajax_referer( 'competition_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }
        
        $result_id = intval( $_POST['result_id'] ?? 0 );
        
        if ( $result_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid result ID' ) );
        }
        
        $db = new Competition_Database();
        $deleted = $db->delete_result( $result_id );
        
        if ( ! $deleted ) {
            wp_send_json_error( array( 'message' => 'Delete failed' ) );
        }
        
        wp_send_json_success( array( 'message' => 'Result deleted' ) );
    }
}