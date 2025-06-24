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
     * Determine the financial year to use for counters.
     *
     * Calculates the UK financial year based on the server clock. If a
     * `CDC_DEFAULT_FINANCIAL_YEAR` constant is defined and represents a more
     * recent year than the calculation, that constant is returned instead.
     *
     * @return string Financial year in `YYYY/YY` format.
     */
    public static function current_financial_year() : string {
        $year  = (int) date( 'Y' );
        $start = ( date( 'n' ) < 4 ) ? $year - 1 : $year;
        $computed = sprintf( '%d/%02d', $start, ( $start + 1 ) % 100 );

        if ( defined( 'CDC_DEFAULT_FINANCIAL_YEAR' ) ) {
            list( $def_start ) = explode( '/', CDC_DEFAULT_FINANCIAL_YEAR );
            if ( $start > (int) $def_start ) {
                return CDC_DEFAULT_FINANCIAL_YEAR;
            }
        }

        return $computed;
    }
}
