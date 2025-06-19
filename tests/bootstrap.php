<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Council_Debt_Counters
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/[\\\\]' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to WP core tests.
$phpunit_polyfills_path = getenv( 'WP_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $phpunit_polyfills_path ) {
    define( 'WP_PHPUNIT_POLYFILLS_PATH', $phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname( dirname( __FILE__ ) ) . '/council-debt-counters.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
