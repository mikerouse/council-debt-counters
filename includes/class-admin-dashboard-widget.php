<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Dashboard_Widget {
    public static function init() {
        add_action( 'wp_dashboard_setup', [ __CLASS__, 'register_widget' ] );
    }

    public static function register_widget() {
        wp_add_dashboard_widget( 'cdc_council_summary', __( 'Council Finance Summary', 'council-debt-counters' ), [ __CLASS__, 'render_widget' ] );
    }

    public static function render_widget() {
        $councils = get_posts( [ 'post_type' => 'council', 'numberposts' => -1 ] );
        if ( empty( $councils ) ) {
            echo '<p>' . esc_html__( 'No council data found.', 'council-debt-counters' ) . '</p>';
            return;
        }
        echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Council', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Debt', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Deficit', 'council-debt-counters' ) . '</th></tr></thead><tbody>';
        foreach ( $councils as $c ) {
            $year = CDC_Utils::current_financial_year();
            $debt = (float) Custom_Fields::get_value( $c->ID, 'total_debt', $year );
            $def  = (float) Custom_Fields::get_value( $c->ID, 'annual_deficit', $year );
            echo '<tr><td>' . esc_html( $c->post_title ) . '</td><td>' . esc_html( number_format_i18n( $debt, 2 ) ) . '</td><td>' . esc_html( number_format_i18n( $def, 2 ) ) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
