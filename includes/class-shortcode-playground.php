<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode_Playground {
    const SLUG = 'cdc-playground';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_cdc_preview_shortcode', [ __CLASS__, 'ajax_preview' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Shortcode Playground', 'council-debt-counters' ),
            __( 'Shortcode Playground', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'debt-counters_page_' . self::SLUG ) {
            return;
        }
        $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
        $use_cdn     = apply_filters( 'cdc_use_cdn', false );

        if ( $use_cdn ) {
            $bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css';
            $bootstrap_js  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js';
        } else {
            $bootstrap_css = plugins_url( 'public/css/bootstrap.min.css', $plugin_file );
            $bootstrap_js  = plugins_url( 'public/js/bootstrap.bundle.min.js', $plugin_file );
        }

        wp_enqueue_style( 'bootstrap-5', $bootstrap_css, [], '5.3.1' );
        wp_enqueue_script( 'bootstrap-5', $bootstrap_js, [], '5.3.1', true );
        wp_enqueue_script(
            'cdc-playground',
            plugins_url( 'admin/js/playground.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [ 'jquery' ],
            '0.1.0',
            true
        );
        wp_localize_script( 'cdc-playground', 'cdcPlay', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );
    }

    public static function render() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/shortcode-playground-page.php';
    }

    public static function ajax_preview() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }
        $shortcode = wp_unslash( $_POST['shortcode'] ?? '' );
        echo do_shortcode( $shortcode );
        wp_die();
    }
}
