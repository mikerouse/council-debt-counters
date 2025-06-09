<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class License_Manager {
    const OPTION_KEY = 'cdc_license_key';
    const OPTION_VALID = 'cdc_license_valid';

    /**
     * Determine if the installed license is valid.
     * This is a placeholder for real validation logic.
     */
    public static function is_valid() {
        return (bool) get_option( self::OPTION_VALID );
    }

    /**
     * Return the stored license key.
     */
    public static function get_license_key() {
        return get_option( self::OPTION_KEY, '' );
    }
}
