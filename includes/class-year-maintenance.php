<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ensure all councils use 2023/24 as the only enabled year and
 * mark councils without Current Liabilities as under review.
 */
class Year_Maintenance {
    const OPTION = 'cdc_year_lock_2023';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'apply' ], 15 );
    }

    public static function apply() {
        if ( '1' === get_option( self::OPTION, '0' ) ) {
            return;
        }
        $year = '2023/24';
        $posts = get_posts([
            'post_type'   => 'council',
            'numberposts' => -1,
            'post_status' => [ 'publish', 'draft', 'under_review' ],
            'fields'      => 'ids',
        ]);
        foreach ( $posts as $id ) {
            update_post_meta( $id, 'cdc_default_financial_year', $year );
            update_post_meta( $id, 'cdc_enabled_years', [ $year ] );
            $val = Custom_Fields::get_value( $id, 'current_liabilities', $year );
            if ( '' === $val || null === $val ) {
                update_post_meta( $id, 'cdc_under_review', '1' );
                if ( 'under_review' === get_post_status( $id ) ) {
                    wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
                }
            } else {
                delete_post_meta( $id, 'cdc_under_review' );
            }
        }
        update_option( self::OPTION, '1' );
    }
}
