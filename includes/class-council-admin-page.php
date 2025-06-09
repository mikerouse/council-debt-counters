<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Council_Admin_Page {
    const PAGE_SLUG = 'cdc-manage-councils';

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_page' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_head', [ __CLASS__, 'maybe_acf_head' ] );
    }

    public static function add_page() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Councils', 'council-debt-counters' ),
            __( 'Councils', 'council-debt-counters' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'debt-counters_page_' . self::PAGE_SLUG ) {
            return;
        }
        wp_enqueue_style( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css', [], '5.3.1' );
        wp_enqueue_script( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js', [], '5.3.1', true );
        if ( function_exists( 'acf_enqueue_uploader' ) ) {
            acf_enqueue_uploader();
        }
    }

    public static function maybe_acf_head() {
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'debt-counters_page_' . self::PAGE_SLUG && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
            if ( function_exists( 'acf_form_head' ) ) {
                acf_form_head();
            }
        }
    }

    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/councils-page.php';
    }
}
