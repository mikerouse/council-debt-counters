<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Whistleblower_Reports_Page {
    const SLUG = 'cdc-whistleblower-reports';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Whistleblower Reports', 'council-debt-counters' ),
            __( 'Whistleblower Reports', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/whistleblower-reports-page.php';
    }
}
