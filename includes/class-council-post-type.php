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
        add_action( 'admin_menu', [ __CLASS__, 'maybe_hide_add_new' ], 99 );
        add_action( 'load-post-new.php', [ __CLASS__, 'enforce_limit' ] );
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
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'supports'     => [ 'title' ],
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
     * Hide Add New menu item when limit reached and license invalid.
     */
    public static function maybe_hide_add_new() {
        if ( License_Manager::is_valid() ) {
            return;
        }
        if ( self::count_councils() >= 2 ) {
            global $submenu;
            if ( isset( $submenu['edit.php?post_type=council'][10] ) ) {
                unset( $submenu['edit.php?post_type=council'][10] );
            }
        }
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
}
