<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Figure_Submission_IPs_Page {
    const SLUG = 'cdc-figure-ips';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
    }

    public static function add_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Submission IPs', 'council-debt-counters' ),
            __( 'Submission IPs', 'council-debt-counters' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT pm.meta_value AS ip, COUNT(*) AS cnt FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.post_type = %s AND pm.meta_key = 'ip_address' GROUP BY pm.meta_value ORDER BY cnt DESC", Figure_Submission_Form::CPT ) );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Submission IPs', 'council-debt-counters' ) . '</h1>';
        if ( empty( $results ) ) {
            echo '<p>' . esc_html__( 'No submissions found.', 'council-debt-counters' ) . '</p>';
        } else {
            echo '<table class="widefat fixed striped"><thead><tr><th>' . esc_html__( 'IP Address', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Count', 'council-debt-counters' ) . '</th></tr></thead><tbody>';
            foreach ( $results as $row ) {
                $link = admin_url( 'admin.php?page=' . Figure_Submissions_Page::SLUG . '&ip=' . rawurlencode( $row->ip ) );
                echo '<tr><td><a href="' . esc_url( $link ) . '">' . esc_html( $row->ip ) . '</a></td><td>' . intval( $row->cnt ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
}
