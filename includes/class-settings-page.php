<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CouncilDebtCounters\Docs_Manager;
use CouncilDebtCounters\Council_Admin_Page;

class Settings_Page {

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
        register_setting( 'council-debt-counters', License_Manager::OPTION_KEY );
        register_setting( 'council-debt-counters', License_Manager::OPTION_VALID );
        register_setting( 'council-debt-counters', 'cdc_openai_api_key' );
        register_setting( 'council-debt-counters', 'cdc_openai_model', [ 'type' => 'string', 'default' => 'gpt-3.5-turbo' ] );
        register_setting( 'council-debt-counters', 'cdc_enabled_counters', [ 'type' => 'array', 'default' => [] ] );
        register_setting(
            'council-debt-counters',
            'cdc_log_level',
            [
                'type'              => 'string',
                'default'           => 'standard',
                'sanitize_callback' => [ __CLASS__, 'sanitize_log_level' ],
            ]
        );
    }

    public static function sanitize_log_level( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, [ 'verbose', 'standard', 'quiet' ], true ) ? $value : 'standard';
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
