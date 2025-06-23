<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Moderation_Log {
	const LOG_FILENAME = 'moderation.log';

	public static function log_action( string $message ) {
		$file          = plugin_dir_path( __DIR__ ) . self::LOG_FILENAME;
				$entry = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $message . "\n";
		file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );
	}

	public static function get_log() {
		$file = plugin_dir_path( __DIR__ ) . self::LOG_FILENAME;
		return file_exists( $file ) ? file_get_contents( $file ) : '';
	}

	public static function clear_log() {
		$file = plugin_dir_path( __DIR__ ) . self::LOG_FILENAME;
		if ( file_exists( $file ) ) {
			file_put_contents( $file, '' );
		}
	}
}
