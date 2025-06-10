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
        // Enqueue Bootstrap (CSS/JS)
        wp_enqueue_style( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css', [], '5.3.1' );
        wp_enqueue_script( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js', [], '5.3.1', true );
        // Enqueue counter CSS/JS for real-time counter in admin
        wp_enqueue_style( 'cdc-counter', plugins_url( 'public/css/counter.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_enqueue_script( 'cdc-counter', plugins_url( 'public/js/counter.js', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0', true );
        if ( function_exists( 'acf_enqueue_uploader' ) ) {
            acf_enqueue_uploader();
        }
        wp_enqueue_script(
            'cdc-council-form',
            plugins_url( 'admin/js/council-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0',
            true
        );
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
