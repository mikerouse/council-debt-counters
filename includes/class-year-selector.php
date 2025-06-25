<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Year_Selector {
    const NONCE = 'cdc_year_select';

    public static function init() {
        add_filter( 'the_content', [ __CLASS__, 'inject_selector' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
        add_action( 'wp_ajax_cdc_select_year', [ __CLASS__, 'ajax_year' ] );
        add_action( 'wp_ajax_nopriv_cdc_select_year', [ __CLASS__, 'ajax_year' ] );
    }

    public static function register_assets() {
        $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
        wp_register_style( 'cdc-year-overlay', plugins_url( 'public/css/year-overlay.css', $plugin_file ), [], '0.1.0' );
        wp_register_script( 'cdc-year-selector', plugins_url( 'public/js/year-selector.js', $plugin_file ), [ 'bootstrap-5' ], '0.1.0', true );
    }

    public static function inject_selector( $content ) {
        if ( ! is_singular( 'council' ) ) {
            return $content;
        }
        wp_enqueue_style( 'bootstrap-5' );
        wp_enqueue_style( 'cdc-year-overlay' );
        wp_enqueue_script( 'bootstrap-5' );
        wp_enqueue_script( 'cdc-year-selector' );
        wp_localize_script( 'cdc-year-selector', 'cdcYear', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( self::NONCE ),
            'postId'  => get_the_ID(),
            'years'   => Docs_Manager::financial_years(),
            'current' => CDC_Utils::current_financial_year(),
        ] );
        $selector  = '<div class="cdc-year-selector mb-3"><label for="cdc-year-select" class="me-2">' . esc_html__( 'Financial Year', 'council-debt-counters' ) . '</label>';
        $selector .= '<select id="cdc-year-select" class="form-select d-inline w-auto"></select></div>';
        return $selector . '<div id="cdc-year-container">' . $content . '</div>';
    }

    private static function validate_year( string $year ) : string {
        $year = sanitize_text_field( $year );
        return preg_match( '/^\d{4}\/\d{2}$/', $year ) ? $year : '';
    }

    public static function ajax_year() {
        check_ajax_referer( self::NONCE, 'nonce' );
        $post_id = intval( $_POST['post_id'] ?? 0 );
        $year    = self::validate_year( $_POST['year'] ?? '' );
        if ( ! $post_id || '' === $year ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request.', 'council-debt-counters' ) ], 400 );
        }
        $post_obj = get_post( $post_id );
        if ( ! $post_obj || 'council' !== $post_obj->post_type ) {
            wp_send_json_error( [ 'message' => __( 'Not found.', 'council-debt-counters' ) ], 404 );
        }
        global $post;
        $GLOBALS['cdc_selected_year'] = $year;
        $post = $post_obj;
        setup_postdata( $post );
        $html = apply_filters( 'the_content', $post->post_content );
        wp_reset_postdata();
        wp_send_json_success( [ 'html' => $html ] );
    }
}
