<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide various calculation and audit tools for admins.
 */
class Calculations_Page {
    const SLUG = 'cdc-calculations';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Calculations', 'council-debt-counters' ),
            __( 'Calculations', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render' ]
        );
    }

    /**
     * Handle audit actions.
     */
    private static function handle_action() {
        if ( empty( $_POST['cdc_calc_action'] ) || empty( $_POST['cdc_council'] ) ) {
            return;
        }
        if ( ! check_admin_referer( 'cdc_calc_action', 'cdc_calc_nonce' ) ) {
            return;
        }
        $cid    = intval( $_POST['cdc_council'] );
        $action = sanitize_key( $_POST['cdc_calc_action'] );

        if ( 'move_2025_to_2023' === $action ) {
            Custom_Fields::move_year_data( $cid, '2025/26', '2023/24' );
            Council_Post_Type::calculate_total_debt( $cid, '2023/24' );
            Error_Logger::log_info( "Moved 2025/26 data to 2023/24 for council $cid" );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Data moved.', 'council-debt-counters' ) . '</p></div>';
        } elseif ( 'check_zero' === $action ) {
            $fields = Custom_Fields::get_fields();
            foreach ( $fields as $f ) {
                $val = Custom_Fields::get_value( $cid, $f->name, '2023/24' );
                if ( '0' === (string) $val || '' === (string) $val ) {
                    Error_Logger::log_info( "Zero value for {$f->name} on council $cid" );
                }
            }
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Zero values logged.', 'council-debt-counters' ) . '</p></div>';
        }
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        self::handle_action();
        include plugin_dir_path( __DIR__ ) . 'admin/views/calculations-page.php';
    }
}
