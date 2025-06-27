<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Power_Editor_Page {
    const SLUG = 'cdc-power-editor';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_cdc_power_save', [ __CLASS__, 'ajax_save' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Power Editor', 'council-debt-counters' ),
            __( 'Power Editor', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'debt-counters_page_' . self::SLUG !== $hook ) {
            return;
        }
        $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
        $use_cdn     = apply_filters( 'cdc_use_cdn', (bool) get_option( 'cdc_use_cdn_assets', 0 ) );

        if ( $use_cdn ) {
            $bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css';
            $bootstrap_js  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js';
        } else {
            $bootstrap_css = plugins_url( 'public/css/bootstrap.min.css', $plugin_file );
            $bootstrap_js  = plugins_url( 'public/js/bootstrap.bundle.min.js', $plugin_file );
        }
        wp_enqueue_style( 'bootstrap-5', $bootstrap_css, [], '5.3.1' );
        wp_enqueue_script( 'bootstrap-5', $bootstrap_js, [], '5.3.1', true );

        wp_enqueue_style(
            'cdc-power-editor',
            plugins_url( 'admin/css/power-editor.css', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0'
        );

        wp_enqueue_script(
            'cdc-power-editor',
            plugins_url( 'admin/js/power-editor.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.1',
            true
        );
        wp_localize_script( 'cdc-power-editor', 'cdcPower', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cdc_power_save' ),
        ] );
    }

    public static function ajax_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
        }
        check_ajax_referer( 'cdc_power_save', 'nonce' );

        $cid   = isset( $_POST['cid'] ) ? intval( $_POST['cid'] ) : 0;
        $field = isset( $_POST['field'] ) ? sanitize_key( $_POST['field'] ) : '';
        $value = $_POST['value'] ?? '';
        $year  = isset( $_POST['year'] ) ? sanitize_text_field( $_POST['year'] ) : CDC_Utils::current_financial_year();

        if ( ! $cid || ! $field ) {
            wp_send_json_error();
        }

        Custom_Fields::update_value( $cid, $field, $value, $year );
        if ( 'council_closed' === $field && $value ) {
            Custom_Fields::update_value( $cid, 'status_message', __( 'This council no longer exists', 'council-debt-counters' ), CDC_Utils::current_financial_year() );
            Custom_Fields::update_value( $cid, 'status_message_type', 'warning', CDC_Utils::current_financial_year() );
        }
        delete_post_meta( $cid, 'cdc_na_' . $field );
        $tab = Custom_Fields::get_field_tab( $field );
        delete_post_meta( $cid, 'cdc_na_tab_' . $tab );

        $years = (array) get_post_meta( $cid, 'cdc_enabled_years', true );
        if ( ! in_array( $year, $years, true ) ) {
            $years[] = $year;
            update_post_meta( $cid, 'cdc_enabled_years', $years );
        }

        // If at least one tab has figures, mark the council as Active.
        $enabled_tabs = (array) get_option( 'cdc_enabled_counters', array() );
        $active       = false;
        foreach ( $enabled_tabs as $tab_key ) {
            if ( '1' !== get_post_meta( $cid, 'cdc_na_tab_' . $tab_key, true ) ) {
                $active = true;
                break;
            }
        }
        if ( $active ) {
            wp_update_post( [ 'ID' => $cid, 'post_status' => 'publish' ] );
            delete_post_meta( $cid, 'cdc_under_review' );
        }

        wp_send_json_success();
    }

    public static function render_page() {
        include dirname( __DIR__ ) . '/admin/views/power-editor-page.php';
    }
}
