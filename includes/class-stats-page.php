<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Stats_Page {
    const PAGE_SLUG = 'cdc-stats';
    const OPTION_KEY = 'cdc_search_stats';
    const SHARE_KEY = 'cdc_share_stats';
    const MAX_ENTRIES = 1000;

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
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Search Stats', 'council-debt-counters' ) . '</h1>';
        $stats = get_option( self::OPTION_KEY, [] );
        if ( empty( $stats ) ) {
            echo '<p>' . esc_html__( 'No searches logged yet.', 'council-debt-counters' ) . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Search Term', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Count', 'council-debt-counters' ) . '</th></tr></thead><tbody>';
            foreach ( $stats as $term => $count ) {
                echo '<tr><td>' . esc_html( $term ) . '</td><td>' . intval( $count ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h1 class="mt-5">' . esc_html__( 'Most Shared Councils', 'council-debt-counters' ) . '</h1>';
        $shares = get_option( self::SHARE_KEY, [] );
        if ( empty( $shares ) ) {
            echo '<p>' . esc_html__( 'No shares logged yet.', 'council-debt-counters' ) . '</p>';
        } else {
            arsort( $shares );
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Council', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Shares', 'council-debt-counters' ) . '</th></tr></thead><tbody>';
            foreach ( $shares as $id => $count ) {
                echo '<tr><td>' . esc_html( get_the_title( $id ) ) . '</td><td>' . intval( $count ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public static function log_search( string $term ) {
        $term  = mb_strtolower( $term );
        $stats = get_option( self::OPTION_KEY, [] );
        if ( ! isset( $stats[ $term ] ) ) {
            $stats[ $term ] = 0;
        }
        $stats[ $term ]++;
        if ( count( $stats ) > self::MAX_ENTRIES ) {
            $stats = array_slice( $stats, -self::MAX_ENTRIES, null, true );
        }
        update_option( self::OPTION_KEY, $stats, false );
    }

    public static function log_share( int $id ) {
        $shares = get_option( self::SHARE_KEY, [] );
        if ( ! isset( $shares[ $id ] ) ) {
            $shares[ $id ] = 0;
        }
        $shares[ $id ]++;
        if ( count( $shares ) > self::MAX_ENTRIES ) {
            $shares = array_slice( $shares, -self::MAX_ENTRIES, null, true );
        }
        update_option( self::SHARE_KEY, $shares, false );
    }
}
