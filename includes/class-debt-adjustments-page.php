<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Debt_Adjustments_Page {
    const SLUG = 'cdc-debt-adjustments';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Debt Adjustments', 'council-debt-counters' ),
            __( 'Debt Adjustments', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/debt-adjustments-page.php';
    }

    public static function add_adjustment( int $council_id, float $amount, string $note = '' ) {
        $entries = get_post_meta( $council_id, 'cdc_debt_adjustments', true );
        if ( ! is_array( $entries ) ) {
            $entries = [];
        }
        $entries[] = [
            'amount' => $amount,
            'note'   => $note,
            'date'   => current_time( 'mysql' ),
        ];
        update_post_meta( $council_id, 'cdc_debt_adjustments', $entries );
        Error_Logger::log( 'Debt adjustment of ' . $amount . ' added to council ' . $council_id );
    }
}
