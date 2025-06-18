<?php
/**
 * Plugin Name: Council Debt Counters
 * Description: Animated counters visualising council debt figures.
 * Version: 0.2.0
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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-error-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-docs-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-license-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-post-type.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-fields.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-openai-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-debt-adjustments-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-whistleblower-form.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-dashboard-widget.php';

register_activation_hook( __FILE__, function() {
    \CouncilDebtCounters\Custom_Fields::install();
    \CouncilDebtCounters\Docs_Manager::install();
} );

add_action( 'plugins_loaded', function() {
    \CouncilDebtCounters\Error_Logger::init();
    \CouncilDebtCounters\Settings_Page::init();
    \CouncilDebtCounters\Council_Post_Type::init();
    \CouncilDebtCounters\Council_Admin_Page::init();
    \CouncilDebtCounters\Custom_Fields::init();
    \CouncilDebtCounters\Docs_Manager::init();
    \CouncilDebtCounters\Shortcode_Renderer::init();
    \CouncilDebtCounters\Debt_Adjustments_Page::init();
    \CouncilDebtCounters\Data_Loader::init();
    \CouncilDebtCounters\License_Manager::init();
    \CouncilDebtCounters\Whistleblower_Form::init();
    \CouncilDebtCounters\Admin_Dashboard_Widget::init();
} );

/**
 * Git Updater support
 * @link https://git-updater.com/knowledge-base/
 */
add_filter( 'git_updater_plugin_config', function( $config ) {
    $config['council-debt-counters/council-debt-counters.php'] = [
        'slug'       => 'council-debt-counters',
        'repo'       => 'council-debt-counters',
        'owner'      => 'mikerouse', // Change to your GitHub username or org
        'branch'     => 'main', // Or 'master' or your default branch
        'uri'        => 'https://github.com/mikerouse/council-debt-counters',
        'remote_url' => 'https://github.com/mikerouse/council-debt-counters.git',
        'type'       => 'github',
    ];
    return $config;
} );
