<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings_Page {

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    /**
     * Add admin menu page.
     */
    public static function add_menu() {
        add_menu_page(
            __( 'Council Debt Counters', 'council-debt-counters' ),
            __( 'Debt Counters', 'council-debt-counters' ),
            'manage_options',
            'council-debt-counters',
            [ __CLASS__, 'render_page' ],
            'dashicons-chart-line'
        );
    }

    /**
     * Render settings page content.
     */
    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/instructions-page.php';
    }
}
