<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Counter_Manager {

    /**
     * Seconds since the start of the current financial year (1 April).
     */
    public static function seconds_since_fy_start() : int {
        $now = time();
        list( $start_year ) = explode( '/', CDC_Utils::current_financial_year() );
        $start = strtotime( $start_year . '-04-01' );

        return max( 0, $now - $start );
    }

    /**
     * Calculate per-second increment from an annual total.
     */
    public static function per_second_rate( float $annual ) : float {
        return $annual / ( 365 * 24 * 60 * 60 );
    }
}
