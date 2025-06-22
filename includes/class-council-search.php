<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Council_Search {
    const SHORTCODE = 'council_search';

    public static function init() {
        add_shortcode( self::SHORTCODE, [ __CLASS__, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
        add_action( 'wp_ajax_cdc_search_councils', [ __CLASS__, 'ajax_search' ] );
        add_action( 'wp_ajax_nopriv_cdc_search_councils', [ __CLASS__, 'ajax_search' ] );
    }

    public static function register_assets() {
        $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
        wp_register_script( 'cdc-council-search', plugins_url( 'public/js/council-search.js', $plugin_file ), [], '0.1.0', true );
        wp_localize_script( 'cdc-council-search', 'cdcSearch', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cdc_search_nonce' ),
            'message' => __( "If you cannot find your local council in these search results it just means we have not yet analysed it, but we will - and soon!", 'council-debt-counters' ),
        ] );
    }

    public static function render() {
        wp_enqueue_style( 'bootstrap-5' );
        wp_enqueue_script( 'bootstrap-5' );
        wp_enqueue_script( 'cdc-council-search' );
        ob_start();
        ?>
        <div class="cdc-search-widget">
            <input type="text" class="form-control mb-2" id="cdc-council-search" placeholder="<?php esc_attr_e( 'Search for your council…', 'council-debt-counters' ); ?>" autocomplete="off" />
            <div id="cdc-search-results" class="list-group"></div>
            <div id="cdc-search-message" class="mt-2 text-muted small"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_search() {
        check_ajax_referer( 'cdc_search_nonce', 'nonce' );
        $query = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
        if ( strlen( $query ) < 3 ) {
            wp_send_json_success( [] );
        }
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'cdc_search_rate_' . md5( $ip );
        if ( false !== get_transient( $key ) ) {
            wp_send_json_error( [ 'message' => __( "Whoa there! You're searching too quickly, slow it down or the system will confuse you for a robot!", 'council-debt-counters' ) ] );
        }
        set_transient( $key, 1, 2 );
        Stats_Page::log_search( $query );
        $args = [
            'post_type'      => 'council',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $query,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ];
        $posts = get_posts( $args );
        $results = [];
        foreach ( $posts as $id ) {
            $results[] = [
                'title' => get_the_title( $id ),
                'url'   => get_permalink( $id ),
            ];
        }
        wp_send_json_success( $results );
    }
}
