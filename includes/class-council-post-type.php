<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Council_Post_Type {
    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'load-post-new.php', [ __CLASS__, 'enforce_limit' ] );
        add_action( 'acf/save_post', [ __CLASS__, 'sync_title_from_acf' ], 20 );
        add_action( 'acf/save_post', [ __CLASS__, 'calculate_total_debt' ], 20 );
    }

    /**
     * Register the council custom post type.
     */
    public static function register() {
        register_post_type( 'council', [
            'labels' => [
                'name'          => __( 'Councils', 'council-debt-counters' ),
                'singular_name' => __( 'Council', 'council-debt-counters' ),
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_admin_bar'  => false,
            'capability_type'    => 'post',
            'supports'           => [ 'title' ],
            'publicly_queryable' => false,
        ] );
    }

    /**
     * Get count of existing council posts.
     */
    public static function count_councils() {
        $count = wp_count_posts( 'council' );
        return (int) $count->publish + (int) $count->draft + (int) $count->pending;
    }

    /**
     * Prevent creation of more councils when limit reached.
     */
    public static function enforce_limit() {
        $screen = get_current_screen();
        if ( $screen && 'council' === $screen->post_type && ! License_Manager::is_valid() && self::count_councils() >= 2 ) {
            wp_safe_redirect( admin_url( 'edit.php?post_type=council&cdc_limit=1' ) );
            exit;
        }
    }

    public static function sync_title_from_acf( $post_id ) {
        if ( get_post_type( $post_id ) !== 'council' ) {
            return;
        }
        $name = get_field( 'council_name', $post_id );
        if ( ! $name ) {
            return;
        }
        remove_action( 'acf/save_post', [ __CLASS__, 'sync_title_from_acf' ], 20 );
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => sanitize_text_field( $name ),
        ]);
        add_action( 'acf/save_post', [ __CLASS__, 'sync_title_from_acf' ], 20 );
        Error_Logger::log_info( 'Council saved with title: ' . $name );
    }

    public static function calculate_total_debt( $post_id ) {
        if ( get_post_type( $post_id ) !== 'council' ) {
            return;
        }
        // Get all relevant fields
        $external = (float) get_field( 'total_external_borrowing', $post_id );
        $manual   = (float) get_field( 'manual_debt_entry', $post_id ); // If you have a manual field
        $adjust   = 0;
        $entries  = get_post_meta( $post_id, 'cdc_debt_adjustments', true );
        if ( is_array( $entries ) ) {
            foreach ( $entries as $e ) {
                $adjust += (float) $e['amount'];
            }
        }
        // Total debt is just external borrowing + manual + adjustments
        $total = $external + $manual + $adjust;
        update_field( 'total_debt', $total, $post_id );
    }
}
