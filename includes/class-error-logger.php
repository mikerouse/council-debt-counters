<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Error_Logger {
    /**
     * Name of the troubleshooting log file.
     */
    const LOG_FILENAME = 'troubleshooting.log';

    /**
     * Previous PHP error handler before this class was initialised.
     *
     * @var callable|null
     */
    private static $previous_error_handler = null;

    /**
     * Previously registered shutdown handler, if any.
     * PHP does not provide a way to remove shutdown handlers so we store
     * the existing callback and re-register it when cleaning up.
     *
     * @var callable|null
     */
    private static $previous_shutdown_handler = null;

    /**
     * Flag for whether our handlers should run.
     * Used when cleaning up on plugin deactivation.
     *
     * @var bool
     */
    private static $active = false;

    public static function log_info( string $message ) {
        self::log( 'INFO: ' . $message );
    }

    public static function init() {
        // Store existing handlers so they can be restored on deactivation.
        self::$previous_error_handler    = set_error_handler( [ __CLASS__, 'handle_error' ] );
        self::$previous_shutdown_handler = null; // PHP doesn't expose previously registered shutdown callbacks.

        register_shutdown_function( [ __CLASS__, 'handle_shutdown' ] );
        self::$active = true;
    }

    /**
     * Restore original handlers and disable logging.
     * Called via plugin deactivation hook.
     */
    public static function cleanup() {
        if ( null !== self::$previous_error_handler ) {
            set_error_handler( self::$previous_error_handler );
        } else {
            restore_error_handler();
        }

        self::$active = false;

        if ( null !== self::$previous_shutdown_handler ) {
            register_shutdown_function( self::$previous_shutdown_handler );
        }
    }

    public static function log( string $message ) {
        $file = self::get_log_file_path();
        $entry = '[' . date( 'Y-m-d H:i:s' ) . "] " . $message . "\n";
        file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );
    }

    public static function handle_error( $errno, $errstr, $errfile, $errline ) {
        if ( ! self::$active ) {
            return false;
        }

        if ( ! ( error_reporting() & $errno ) ) {
            return false;
        }

        self::log( "Error {$errno} at {$errfile}:{$errline} - {$errstr}" );
        return false; // Let default handler run as well
    }

    public static function handle_shutdown() {
        if ( ! self::$active ) {
            return;
        }

        $error = error_get_last();
        if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ] ) ) {
            self::log( "Fatal {$error['type']} at {$error['file']}:{$error['line']} - {$error['message']}" );
        }
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
