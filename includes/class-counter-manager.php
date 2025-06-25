<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\CDC_Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Counter_Manager {

    /**
     * Seconds since the start of the current financial year (1 April).
     */
    public static function seconds_since_fy_start( string $year = '' ) : int {
        if ( '' === $year ) {
            $year = CDC_Utils::current_financial_year();
        }
        list( $start_year ) = explode( '/', $year );
        $start_year = (int) $start_year;

        $now   = time();
        $start = strtotime( $start_year . '-04-01' );
        $end   = strtotime( ( $start_year + 1 ) . '-04-01' );

        $elapsed = max( 0, $now - $start );
        return min( $elapsed, $end - $start );
    }

    /**
     * Calculate per-second increment from an annual total.
     */
    public static function per_second_rate( float $annual ) : float {
        return $annual / ( 365 * 24 * 60 * 60 );
    }
}
