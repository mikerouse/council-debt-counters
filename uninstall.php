<?php
/**
 * Uninstall script for Council Debt Counters.
 *
 * @package CouncilDebtCounters
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Options to remove.
$option_names = array(
	'cdc_openai_api_key',
	'cdc_enabled_counters',
	'cdc_log_level',
	'cdc_waste_report_count',
);

foreach ( $option_names as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}

// Drop custom tables.
global $wpdb;
$values_table = $wpdb->prefix . 'cdc_field_values';
$columns      = $wpdb->get_col( "DESC $values_table", 0 );
if ( in_array( 'financial_year', $columns, true ) ) {
        $wpdb->query( "ALTER TABLE $values_table DROP COLUMN financial_year" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
$tables = array(
        $wpdb->prefix . 'cdc_fields',
        $wpdb->prefix . 'cdc_field_values',
        $wpdb->prefix . 'cdc_documents',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
}

// Delete custom post types.
$council_posts = get_posts(
	array(
		'post_type'   => 'council',
		'numberposts' => -1,
		'post_status' => 'any',
		'fields'      => 'ids',
	)
);

foreach ( $council_posts as $council_id ) {
	wp_delete_post( $council_id, true );
}

$waste_reports = get_posts(
	array(
		'post_type'   => 'waste_report',
		'numberposts' => -1,
		'post_status' => 'any',
		'fields'      => 'ids',
	)
);

foreach ( $waste_reports as $report_id ) {
	wp_delete_post( $report_id, true );
}

// Remove uploaded documents and log file.
$docs_dir = plugin_dir_path( __FILE__ ) . 'docs/';
if ( file_exists( $docs_dir ) ) {
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $docs_dir, RecursiveDirectoryIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}
	rmdir( $docs_dir );
}

$log_file = plugin_dir_path( __FILE__ ) . 'troubleshooting.log';
if ( file_exists( $log_file ) ) {
	unlink( $log_file );
}
