<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class License_Manager {
    const OPTION_KEY = 'cdc_license_key';
    const OPTION_VALID = 'cdc_license_valid';
    const API_ENDPOINT = 'https://mikerouse.co.uk/wp-json/wcls/v1/check';

    /**
     * Register hooks for AJAX and assets.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_cdc_check_license', [ __CLASS__, 'ajax_check_license' ] );
    }

    /**
     * Determine if the installed license is valid.
     * This is a placeholder for real validation logic.
     */
    public static function is_valid() {
        return (bool) get_option( self::OPTION_VALID );
    }

    /**
     * Return the stored license key.
     */
    public static function get_license_key() {
        return get_option( self::OPTION_KEY, '' );
    }

    /**
     * Enqueue JS for license checking.
     */
    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_council-debt-counters' ) {
            return;
        }
        wp_enqueue_script(
            'cdc-license-check',
            plugins_url( 'admin/js/license-check.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0',
            true
        );
        wp_localize_script( 'cdc-license-check', 'CDC_LICENSE_CHECK', [
            'nonce' => wp_create_nonce( 'cdc_check_license' ),
        ] );
    }

    /**
     * Validate a license key with the remote API.
     */
    public static function validate_key( string $key ) {
        $args = [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [ 'licence_key' => $key ] ),
            'timeout' => 20,
        ];

        $response = wp_remote_post( self::API_ENDPOINT, $args );
        if ( is_wp_error( $response ) ) {
            Error_Logger::log( 'License check failed: ' . $response->get_error_message() );
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['valid'] ) && $data['valid'] && empty( $data['expired'] ) ) {
            return true;
        }

        return new \WP_Error( 'invalid_license', __( 'License key is invalid or expired.', 'council-debt-counters' ) );
    }

    /**
     * AJAX callback to verify and store the license key.
     */
    public static function ajax_check_license() {
        check_ajax_referer( 'cdc_check_license', 'nonce' );
        $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
        if ( empty( $key ) ) {
            wp_send_json_error( [ 'message' => __( 'License key missing.', 'council-debt-counters' ) ] );
        }

        $result = self::validate_key( $key );
        update_option( self::OPTION_KEY, $key );
        if ( true === $result ) {
            update_option( self::OPTION_VALID, 1 );
            wp_send_json_success( [ 'message' => __( 'License confirmed.', 'council-debt-counters' ) ] );
        } else {
            update_option( self::OPTION_VALID, 0 );
            $msg = is_wp_error( $result ) ? $result->get_error_message() : __( 'License invalid.', 'council-debt-counters' );
            wp_send_json_error( [ 'message' => $msg ] );
        }
    }
}
