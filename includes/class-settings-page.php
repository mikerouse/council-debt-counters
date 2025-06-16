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
    }

    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/instructions-page.php';
    }

    public static function render_docs_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/docs-manager-page.php';
    }

    public static function render_troubleshooting_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/troubleshooting-page.php';
    }
}
