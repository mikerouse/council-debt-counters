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
        $year = date( 'Y' );
        $now  = time();
        $start = strtotime( "$year-04-01" );
        if ( $now < $start ) {
            $start = strtotime( ( $year - 1 ) . '-04-01' );
        }
        return max( 0, $now - $start );
    }

    /**
     * Calculate per-second increment from an annual total.
     */
    public static function per_second_rate( float $annual ) : float {
        return $annual / ( 365 * 24 * 60 * 60 );
    }
}
