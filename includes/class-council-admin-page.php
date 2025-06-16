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
        // Register submenu after the main plugin menu is added.
        add_action( 'admin_menu', [ __CLASS__, 'add_page' ], 11 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_post_cdc_save_council', [ __CLASS__, 'handle_save' ] );
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
        wp_enqueue_script(
            'cdc-council-form',
            plugins_url( 'admin/js/council-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0',
            true
        );
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_save_council' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $title   = sanitize_text_field( $_POST['post_title'] ?? '' );

        if ( $post_id ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => $title ] );
        } else {
            $post_id = wp_insert_post( [ 'post_type' => 'council', 'post_status' => 'publish', 'post_title' => $title ] );
        }

        $fields = Custom_Fields::get_fields();
        foreach ( $fields as $field ) {
            $value = $_POST['cdc_fields'][ $field->id ] ?? '';
            Custom_Fields::update_value( $post_id, $field->name, wp_unslash( $value ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        exit;
    }

    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/councils-page.php';
    }
}
