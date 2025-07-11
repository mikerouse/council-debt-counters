<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Council_Post_Type {
    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'save_post_council', [ __CLASS__, 'calculate_total_debt' ], 20, 1 );
        add_action( 'save_post_council', [ __CLASS__, 'calculate_total_income' ], 20, 1 );
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
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_admin_bar'  => false,
            'capability_type'    => 'post',
            'supports'           => [ 'title' ],
            'publicly_queryable' => true,
            'show_in_rest'       => true,
            'has_archive'        => true,
            'rewrite'            => [ 'slug' => 'council' ],
        ] );

        register_post_status( 'under_review', [
            'label'                     => _x( 'Under Review', 'post', 'council-debt-counters' ),
            'public'                    => true,
            'publicly_queryable'        => true,
            'exclude_from_search'       => false,
            'internal'                  => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Under Review (%s)', 'Under Review (%s)', 'council-debt-counters' ),
        ] );
    }

    /**
     * Get count of existing council posts.
     */
    public static function count_councils() {
        $count = wp_count_posts( 'council' );
        return (int) $count->publish + (int) $count->draft + (int) $count->pending;
    }



    public static function calculate_total_debt( $post_id, string $year = '' ) {
        if ( get_post_type( $post_id ) !== 'council' ) {
            return;
        }

        if ( empty( $year ) ) {
            $year = CDC_Utils::current_financial_year();
        }

        $current_liabilities = Custom_Fields::get_value( $post_id, 'current_liabilities', $year );
        $long_term           = Custom_Fields::get_value( $post_id, 'long_term_liabilities', $year );
        $lease_pfi           = Custom_Fields::get_value( $post_id, 'finance_lease_pfi_liabilities', $year );
        $manual              = Custom_Fields::get_value( $post_id, 'manual_debt_entry', $year );
        $entries             = get_post_meta( $post_id, 'cdc_debt_adjustments', true );

        // If we have no data for this year, don't overwrite any existing total with zero.
        $has_data = false;
        foreach ( array( $current_liabilities, $long_term, $lease_pfi, $manual ) as $val ) {
            if ( '' !== $val && null !== $val ) {
                $has_data = true;
                break;
            }
        }
        if ( ! $has_data && empty( $entries ) ) {
            return;
        }

        $current_liabilities = (float) $current_liabilities;
        $long_term           = (float) $long_term;
        $lease_pfi           = (float) $lease_pfi;
        $manual              = (float) $manual;

        $adjust = 0;
        if ( is_array( $entries ) ) {
            foreach ( $entries as $e ) {
                $adjust += (float) $e['amount'];
            }
        }

        $total = $current_liabilities + $long_term + $lease_pfi + $manual + $adjust;
        Custom_Fields::update_value( $post_id, 'total_debt', $total, $year );
    }

    public static function calculate_total_income( $post_id, string $year = '' ) {
        if ( get_post_type( $post_id ) !== 'council' ) {
            return;
        }

        if ( empty( $year ) ) {
            $year = CDC_Utils::current_financial_year();
        }

        $service  = Custom_Fields::get_value( $post_id, 'non_council_tax_income', $year );
        $tax      = Custom_Fields::get_value( $post_id, 'council_tax_general_grants_income', $year );
        $grants   = Custom_Fields::get_value( $post_id, 'government_grants_income', $year );
        $other    = Custom_Fields::get_value( $post_id, 'all_other_income', $year );

        $has_data = false;
        foreach ( array( $service, $tax, $grants, $other ) as $val ) {
            if ( '' !== $val && null !== $val ) {
                $has_data = true;
                break;
            }
        }
        if ( ! $has_data ) {
            return;
        }

        $total = (float) $service + (float) $tax + (float) $grants + (float) $other;
        Custom_Fields::update_value( $post_id, 'total_income', $total, $year );
    }
}
