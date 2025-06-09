<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Error_Logger {
    const LOG_FILENAME = 'troubleshooting.log';

    public static function init() {
        set_error_handler( [ __CLASS__, 'handle_error' ] );
    }

    public static function log( string $message ) {
        $file = self::get_log_file_path();
        $entry = '[' . date( 'Y-m-d H:i:s' ) . "] " . $message . "\n";
        file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );
    }

    public static function handle_error( $errno, $errstr, $errfile, $errline ) {
        if ( ! ( error_reporting() & $errno ) ) {
            return false;
        }
        self::log( "Error {$errno} at {$errfile}:{$errline} - {$errstr}" );
        return false; // Let default handler run as well
    }

    public static function get_log() {
        $file = self::get_log_file_path();
        return file_exists( $file ) ? file_get_contents( $file ) : '';
    }

    public static function clear_log() {
        $file = self::get_log_file_path();
        if ( file_exists( $file ) ) {
            file_put_contents( $file, '' );
        }
    }

    private static function get_log_file_path() {
        return plugin_dir_path( dirname( __FILE__ ) ) . self::LOG_FILENAME;
    }
}
