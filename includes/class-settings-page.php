<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CouncilDebtCounters\Docs_Manager;
use CouncilDebtCounters\Council_Admin_Page;

class Settings_Page {

    const FONT_CHOICES = [ 'Oswald', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Source Sans Pro' ];

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu() {
        add_menu_page(
            __( 'Council Debt Counters', 'council-debt-counters' ),
            __( 'Debt Counters', 'council-debt-counters' ),
            'manage_options',
            'council-debt-counters',
            [ __CLASS__, 'render_page' ],
            'dashicons-chart-line'
        );

        add_submenu_page(
            'council-debt-counters',
            __( 'Licence Keys and Addons', 'council-debt-counters' ),
            __( 'Licence Keys and Addons', 'council-debt-counters' ),
            'manage_options',
            'cdc-license-keys',
            [ __CLASS__, 'render_license_page' ]
        );

        add_submenu_page(
            'council-debt-counters',
            __( 'Settings', 'council-debt-counters' ),
            __( 'Settings', 'council-debt-counters' ),
            'manage_options',
            'cdc-settings',
            [ __CLASS__, 'render_settings_page' ]
        );

        add_submenu_page(
            'council-debt-counters',
            __( 'Import & Export', 'council-debt-counters' ),
            __( 'Import & Export', 'council-debt-counters' ),
            'manage_options',
            'cdc-import-export',
            [ __CLASS__, 'render_import_export_page' ]
        );

        // Only add unique submenus here (do not duplicate "Councils")
        add_submenu_page(
            'council-debt-counters',
            __( 'Manage Documents', 'council-debt-counters' ),
            __( 'Manage Documents', 'council-debt-counters' ),
            'manage_options',
            'cdc-manage-docs',
            [ __CLASS__, 'render_docs_page' ]
        );
        add_submenu_page(
            'council-debt-counters',
            __( 'Troubleshooting', 'council-debt-counters' ),
            __( 'Troubleshooting', 'council-debt-counters' ),
            'manage_options',
            'cdc-troubleshooting',
            [ __CLASS__, 'render_troubleshooting_page' ]
        );
    }

    public static function register_settings() {
        // Options on the Licence page.
        register_setting( 'cdc_license', License_Manager::OPTION_KEY );
        register_setting( 'cdc_license', License_Manager::OPTION_VALID );
        register_setting( 'cdc_license', 'cdc_openai_api_key' );
        register_setting( 'cdc_license', 'cdc_recaptcha_site_key' );
        register_setting( 'cdc_license', 'cdc_recaptcha_secret_key' );

        // Options on the Settings page.
        register_setting( 'cdc_settings', 'cdc_openai_model', [ 'type' => 'string', 'default' => 'gpt-3.5-turbo' ] );
        register_setting( 'cdc_settings', 'cdc_enabled_counters', [ 'type' => 'array', 'default' => [] ] );
        register_setting(
            'cdc_settings',
            'cdc_log_level',
            [
                'type'              => 'string',
                'default'           => 'standard',
                'sanitize_callback' => [ __CLASS__, 'sanitize_log_level' ],
            ]
        );
        register_setting(
            'cdc_settings',
            'cdc_counter_font',
            [
                'type'              => 'string',
                'default'           => 'Oswald',
                'sanitize_callback' => [ __CLASS__, 'sanitize_font' ],
            ]
        );
        register_setting(
            'cdc_settings',
            'cdc_counter_weight',
            [
                'type'              => 'string',
                'default'           => '600',
                'sanitize_callback' => [ __CLASS__, 'sanitize_weight' ],
            ]
        );
    }

    public static function sanitize_log_level( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, [ 'verbose', 'standard', 'quiet' ], true ) ? $value : 'standard';
    }

    public static function sanitize_font( $value ) {
        $value = sanitize_text_field( $value );
        return in_array( $value, self::FONT_CHOICES, true ) ? $value : 'Oswald';
    }

    public static function sanitize_weight( $value ) {
        $value = preg_replace( '/[^0-9]/', '', $value );
        if ( $value < 100 || $value > 900 ) {
            return '600';
        }
        return $value;
    }

    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/instructions-page.php';
    }

    public static function render_license_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/license-page.php';
    }

    public static function render_settings_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/settings-page.php';
    }

    public static function render_import_export_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/import-export-page.php';
    }

    public static function render_docs_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/docs-manager-page.php';
    }

    public static function render_troubleshooting_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/troubleshooting-page.php';
    }
}
