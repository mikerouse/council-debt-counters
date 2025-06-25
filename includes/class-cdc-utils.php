<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CDC_Utils {
    /**
     * Resolve a council ID from shortcode attributes.
     *
     * @param array $atts Shortcode attributes.
     * @return int Council post ID.
     */
    public static function resolve_council_id( array $atts ): int {
        $id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;

        if ( 0 === $id && isset( $atts['council_id'] ) ) {
            $id = intval( $atts['council_id'] );
        }

        if ( 0 === $id && ! empty( $atts['council'] ) ) {
            $name = sanitize_text_field( $atts['council'] );
            $post = get_page_by_title( $name, OBJECT, 'council' );
            if ( ! $post ) {
                $post = get_page_by_path( sanitize_title( $name ), OBJECT, 'council' );
            }
            if ( $post ) {
                $id = $post->ID;
            }
        }

        if ( 0 === $id && is_singular( 'council' ) ) {
            $id = get_the_ID();
        }

        return $id;
    }

    /**
     * Get the current financial year in YYYY/YY format.
     */
    public static function current_financial_year(): string {
        if ( isset( $GLOBALS['cdc_selected_year'] ) && preg_match( '/^\d{4}\/\d{2}$/', $GLOBALS['cdc_selected_year'] ) ) {
            return $GLOBALS['cdc_selected_year'];
        }
        if ( function_exists( '\is_singular' ) && \is_singular( 'council' ) ) {
            $year = get_post_meta( get_the_ID(), 'cdc_default_financial_year', true );
            if ( $year ) {
                return $year;
            }
        }
        if ( class_exists( '\\CouncilDebtCounters\\Docs_Manager' ) && method_exists( '\\CouncilDebtCounters\\Docs_Manager', 'current_financial_year' ) ) {
            return Docs_Manager::current_financial_year();
        }
        $year  = (int) date( 'Y' );
        $start = ( date( 'n' ) < 4 ) ? $year - 1 : $year;
        $end   = $start + 1;
        return sprintf( '%d/%02d', $start, $end % 100 );
    }
}
