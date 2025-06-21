<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Stats_Page {
    const PAGE_SLUG = 'cdc-stats';
    const OPTION_KEY = 'cdc_search_stats';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Stats', 'council-debt-counters' ),
            __( 'Stats', 'council-debt-counters' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Search Stats', 'council-debt-counters' ) . '</h1>';
        $stats = get_option( self::OPTION_KEY, [] );
        if ( empty( $stats ) ) {
            echo '<p>' . esc_html__( 'No searches logged yet.', 'council-debt-counters' ) . '</p></div>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Search Term', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Count', 'council-debt-counters' ) . '</th></tr></thead><tbody>';
        foreach ( $stats as $term => $count ) {
            echo '<tr><td>' . esc_html( $term ) . '</td><td>' . intval( $count ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function log_search( string $term ) {
        $term  = mb_strtolower( $term );
        $stats = get_option( self::OPTION_KEY, [] );
        if ( ! isset( $stats[ $term ] ) ) {
            $stats[ $term ] = 0;
        }
        $stats[ $term ]++;
        update_option( self::OPTION_KEY, $stats, false );
    }
}
