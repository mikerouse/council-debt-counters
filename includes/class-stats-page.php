<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Stats_Page {
    const PAGE_SLUG   = 'cdc-stats';
    const OPTION_KEY  = 'cdc_search_stats';
    const SHARE_KEY   = 'cdc_share_stats';
    const VISIT_KEY   = 'cdc_visit_stats';
    const MAX_ENTRIES = 1000;

    /** Number of minutes for alert threshold window. */
    const ALERT_WINDOW = 15;
    /** Number of visits in ALERT_WINDOW needed to trigger an alert email. */
    const ALERT_THRESHOLD = 50;

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_log_visit' ] );
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

    /**
     * Determine if the current request is likely from a bot or automated system.
     */
    protected static function is_bot() : bool {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ( '' === $ua ) {
            return true;
        }
        $patterns = 'bot|crawl|slurp|spider|curl|python|ai|scrapy|fetch';
        return (bool) preg_match( '/(' . $patterns . ')/i', $ua );
    }

    /**
     * Hook into template_redirect and log visits to council pages.
     */
    public static function maybe_log_visit() {
        if ( is_admin() ) {
            return;
        }
        if ( is_singular( 'council' ) ) {
            $id  = get_queried_object_id();
            $bot = self::is_bot();
            self::log_visit( $id, $bot );
        }
    }

    /**
     * Record a visit event.
     */
    public static function log_visit( int $id, bool $ai ) {
        $visits = get_option( self::VISIT_KEY, [] );
        if ( ! isset( $visits[ $id ] ) ) {
            $visits[ $id ] = [];
        }

        $now = time();
        // Remove entries older than one hour to keep array size manageable.
        $visits[ $id ] = array_values( array_filter(
            $visits[ $id ],
            static function ( $v ) use ( $now ) {
                return isset( $v['t'] ) && ( $v['t'] >= $now - HOUR_IN_SECONDS );
            }
        ) );

        $visits[ $id ][] = [ 't' => $now, 'ai' => $ai ? 1 : 0 ];
        if ( count( $visits[ $id ] ) > self::MAX_ENTRIES ) {
            $visits[ $id ] = array_slice( $visits[ $id ], -self::MAX_ENTRIES );
        }

        update_option( self::VISIT_KEY, $visits, false );

        // Alert admin if threshold exceeded within ALERT_WINDOW minutes.
        $recent = array_filter(
            $visits[ $id ],
            static function ( $v ) use ( $now ) {
                return $v['t'] >= $now - ( self::ALERT_WINDOW * MINUTE_IN_SECONDS );
            }
        );

        if ( count( $recent ) > self::ALERT_THRESHOLD ) {
            $key = 'cdc_alert_' . $id;
            if ( false === get_transient( $key ) ) {
                $title  = get_the_title( $id );
                $email  = get_option( 'admin_email' );
                $subject = sprintf( __( 'High traffic alert for %s', 'council-debt-counters' ), $title );
                $message = sprintf( __( 'More than %d visits to "%s" in the last %d minutes.', 'council-debt-counters' ), self::ALERT_THRESHOLD, $title, self::ALERT_WINDOW );
                wp_mail( $email, $subject, $message );
                set_transient( $key, 1, self::ALERT_WINDOW * MINUTE_IN_SECONDS );
            }
        }
    }

    /**
     * Get visit counts in the last hour for a council.
     */
    public static function get_visit_counts( int $id ) : array {
        $visits = get_option( self::VISIT_KEY, [] );
        $now    = time();
        $human  = 0;
        $ai     = 0;
        if ( isset( $visits[ $id ] ) ) {
            $filtered = array_filter(
                $visits[ $id ],
                static function ( $v ) use ( $now ) {
                    return isset( $v['t'] ) && ( $v['t'] >= $now - HOUR_IN_SECONDS );
                }
            );
            foreach ( $filtered as $v ) {
                if ( ! empty( $v['ai'] ) ) {
                    $ai++;
                } else {
                    $human++;
                }
            }
            // Trim to filtered set to prevent growth
            $visits[ $id ] = array_values( $filtered );
            update_option( self::VISIT_KEY, $visits, false );
        }

        return [ 'human' => $human, 'ai' => $ai ];
    }
}
