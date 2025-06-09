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

        acf_add_local_field_group([
            'key' => 'group_cdc_council',
            'title' => __( 'Council Details', 'council-debt-counters' ),
            'fields' => [
                [
                    'key' => 'field_cdc_council_name',
                    'label' => __( 'Council Name', 'council-debt-counters' ),
                    'name' => 'council_name',
                    'type' => 'text',
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
                ],
                [
                    'key' => 'field_cdc_population',
                    'label' => __( 'Population', 'council-debt-counters' ),
                    'name' => 'population',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_elected_members',
                    'label' => __( 'Elected Members', 'council-debt-counters' ),
                    'name' => 'elected_members',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_council_tax_revenue',
                    'label' => __( 'Annual Council Tax Revenue', 'council-debt-counters' ),
                    'name' => 'council_tax_revenue',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_total_debt',
                    'label' => __( 'Total Debt', 'council-debt-counters' ),
                    'name' => 'total_debt',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_cdc_debt_per_household',
                    'label' => __( 'Debt Per Household', 'council-debt-counters' ),
                    'name' => 'debt_per_household',
                    'type' => 'number',
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
