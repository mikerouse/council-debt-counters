<?php
/**
 * Plugin Name: Council Debt Counters
 * Description: Animated counters visualising council debt figures.
 * Version: 0.1.0
 * Author: Mike Rouse using OpenAI Codex
 * Author URI: https://www.mikerouse.co.uk
 * Text Domain: council-debt-counters
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-counter-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcode-renderer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-data-loader.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-license-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-post-type.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-manager.php';

add_action( 'plugins_loaded', function() {
    \CouncilDebtCounters\Settings_Page::init();
    \CouncilDebtCounters\Council_Post_Type::init();
    \CouncilDebtCounters\ACF_Manager::init();
} );
