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
            // Use the most recent enabled year when viewing a council page.
            $latest = self::latest_enabled_year( get_the_ID() );
            if ( $latest ) {
                return $latest;
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

    /**
     * Get the financial years enabled for a council.
     *
     * @param int $council_id Council post ID. Defaults to current post if viewing a council.
     * @return array List of enabled year strings.
     */
    public static function council_years( int $council_id = 0 ) : array {
        if ( 0 === $council_id && function_exists( '\is_singular' ) && \is_singular( 'council' ) ) {
            $council_id = get_the_ID();
        }

        $years = [];
        if ( class_exists( '\\CouncilDebtCounters\\Docs_Manager' ) && method_exists( '\\CouncilDebtCounters\\Docs_Manager', 'financial_years' ) ) {
            $years = Docs_Manager::financial_years();
        }

        if ( ! $council_id ) {
            return $years;
        }

        $enabled = get_post_meta( $council_id, 'cdc_enabled_years', true );
        if ( ! is_array( $enabled ) || empty( $enabled ) ) {
            return $years;
        }

        return array_values( array_intersect( $years, $enabled ) );
    }

    /**
     * Get the most recent enabled financial year for a council.
     * Falls back to the plugin's current year if none are enabled.
     */
    public static function latest_enabled_year( int $council_id = 0 ) : string {
        $years = self::council_years( $council_id );
        if ( ! empty( $years ) ) {
            // The years array is ordered from newest to oldest.
            return $years[0];
        }
        // Default to plugin's global current year if no enabled years found.
        return Docs_Manager::current_financial_year();
    }

    /**
     * Determine if a council is marked as under review.
     *
     * @param int $council_id Council post ID.
     * @return bool Whether the council is under review.
     */
    public static function is_under_review( int $council_id ): bool {
        if ( ! $council_id ) {
            return false;
        }

        $status = get_post_status( $council_id );
        $flag   = get_post_meta( $council_id, 'cdc_under_review', true );

        return ( 'under_review' === $status || '1' === $flag );
    }
}
