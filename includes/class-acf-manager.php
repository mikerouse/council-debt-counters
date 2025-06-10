<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ACF_Manager {
    /**
     * Register hooks
     */
    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'check_acf_dependency' ] );
        add_action( 'acf/init', [ __CLASS__, 'register_fields' ] );
    }

    /**
     * Display admin notice if ACF is not active.
     */
    public static function check_acf_dependency() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Council Debt Counters requires the Advanced Custom Fields plugin to be installed and activated.', 'council-debt-counters' ) . '</p></div>';
            } );
        }
    }

    /**
     * Register ACF fields for the council post type.
     */
    public static function register_fields() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        $year = date( 'Y' );
        if ( date( 'n' ) < 4 ) {
            $year--;
        }
        $default_date = sprintf( '%04d-04-01', $year );

        acf_add_local_field_group([
            'key' => 'group_cdc_council',
            'title' => __( 'Council Details', 'council-debt-counters' ),
            'fields' => [
                [
                    'key' => 'field_cdc_council_name',
                    'label' => __( 'Council Name', 'council-debt-counters' ),
                    'name' => 'council_name',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_cdc_council_website',
                    'label' => __( 'Council Website URL', 'council-debt-counters' ),
                    'name' => 'council_website',
                    'type' => 'url',
                    'required' => 1,
                ],
                [
                    'key' => 'field_cdc_council_type',
                    'label' => __( 'Council Type', 'council-debt-counters' ),
                    'name' => 'council_type',
                    'type' => 'select',
                    'choices' => [
                        'district'   => __( 'District', 'council-debt-counters' ),
                        'county'     => __( 'County', 'council-debt-counters' ),
                        'unitary'    => __( 'Unitary', 'council-debt-counters' ),
                        'metropolitan' => __( 'Metropolitan', 'council-debt-counters' ),
                        'london_borough' => __( 'London Borough', 'council-debt-counters' ),
                    ],
                    'ui' => 1,
                    'required' => 1,
                ],
                [
                    'key' => 'field_cdc_population',
                    'label' => __( 'Population', 'council-debt-counters' ),
                    'name' => 'population',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_total_debt',
                    'label' => __( 'Total Debt', 'council-debt-counters' ),
                    'name' => 'total_debt',
                    'type' => 'number',
                    'readonly' => 1,
                ],
                [
                    'key' => 'field_cdc_current_liabilities',
                    'label' => __( 'Current Liabilities', 'council-debt-counters' ),
                    'name' => 'current_liabilities',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_short_term_borrowing',
                    'label' => __( 'Short-term Borrowing', 'council-debt-counters' ),
                    'name' => 'short_term_borrowing',
                    'type' => 'number',
                    'instructions' => __( 'Refer to the council\'s Statement of Accounts.', 'council-debt-counters' ),
                ],
                [
                    'key' => 'field_cdc_long_term_liabilities',
                    'label' => __( 'Long Term Liabilities', 'council-debt-counters' ),
                    'name' => 'long_term_liabilities',
                    'type' => 'number',
                    'instructions' => __( 'Refer to the council\'s Statement of Accounts.', 'council-debt-counters' ),
                ],
                [
                    'key' => 'field_cdc_pfi_lease_liabilities',
                    'label' => __( 'PFI or Finance Lease Liabilities', 'council-debt-counters' ),
                    'name' => 'pfi_or_finance_lease_liabilities',
                    'type' => 'number',
                    'instructions' => __( 'Refer to the council\'s Statement of Accounts.', 'council-debt-counters' ),
                ],
                [
                    'key' => 'field_cdc_interest_paid',
                    'label' => __( 'Interest Paid on Debt', 'council-debt-counters' ),
                    'name' => 'interest_paid_on_debt',
                    'type' => 'number',
                    'required' => 1,
                ],
                [
                    'key' => 'field_cdc_mrp',
                    'label' => __( 'Minimum Revenue Provision (Debt Repayment)', 'council-debt-counters' ),
                    'name' => 'minimum_revenue_provision',
                    'type' => 'number',
                    'required' => 1,
                ],
                [
                    'key' => 'field_cdc_band_a_props',
                    'label' => __( 'Properties in Band A', 'council-debt-counters' ),
                    'name' => 'band_a_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_b_props',
                    'label' => __( 'Properties in Band B', 'council-debt-counters' ),
                    'name' => 'band_b_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_c_props',
                    'label' => __( 'Properties in Band C', 'council-debt-counters' ),
                    'name' => 'band_c_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_d_props',
                    'label' => __( 'Properties in Band D', 'council-debt-counters' ),
                    'name' => 'band_d_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_e_props',
                    'label' => __( 'Properties in Band E', 'council-debt-counters' ),
                    'name' => 'band_e_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_f_props',
                    'label' => __( 'Properties in Band F', 'council-debt-counters' ),
                    'name' => 'band_f_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_g_props',
                    'label' => __( 'Properties in Band G', 'council-debt-counters' ),
                    'name' => 'band_g_properties',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_band_h_props',
                    'label' => __( 'Properties in Band H', 'council-debt-counters' ),
                    'name' => 'band_h_properties',
                    'type' => 'number',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'council',
                    ],
                ],
            ],
        ] );
    }
}
