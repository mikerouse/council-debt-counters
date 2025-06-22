<?php
/**
 * Plugin Name: Council Debt Counters
 * Description: Animated counters visualising council debt figures.
 * Version: 0.2.6
 * Author: Mike Rouse using OpenAI Codex
 * Author URI: https://www.mikerouse.co.uk
 * Text Domain: council-debt-counters
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-counter-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcode-renderer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-data-loader.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-error-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-docs-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-license-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-post-type.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-councils-table.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-fields.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-openai-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ai-extractor.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-debt-adjustments-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-whistleblower-reports-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-whistleblower-form.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-figure-submission-form.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-figure-submissions-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-dashboard-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcode-playground.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-council-search.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-stats-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sharing-meta.php';

register_activation_hook(
	__FILE__,
	function () {
		\CouncilDebtCounters\Custom_Fields::install();
		\CouncilDebtCounters\Docs_Manager::install();
	}
);

// Ensure custom error handling does not persist after deactivation.
register_deactivation_hook(
	__FILE__,
	function () {
		\CouncilDebtCounters\Error_Logger::cleanup();
	}
);


add_action(
	'plugins_loaded',
	function () {
		// Initialise error logging; Error_Logger::cleanup() will restore
		// the previous handlers on plugin deactivation.
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
		\CouncilDebtCounters\OpenAI_Helper::init();
		\CouncilDebtCounters\Whistleblower_Form::init();
		\CouncilDebtCounters\Whistleblower_Reports_Page::init();
		\CouncilDebtCounters\Admin_Dashboard_Widget::init();
		\CouncilDebtCounters\Shortcode_Playground::init();
		\CouncilDebtCounters\Council_Search::init();
		\CouncilDebtCounters\Stats_Page::init();
		\CouncilDebtCounters\Figure_Submission_Form::init();
		\CouncilDebtCounters\Figure_Submissions_Page::init();
		\CouncilDebtCounters\Sharing_Meta::init();
	}
);

/**
 * Git Updater support
 * @link https://git-updater.com/knowledge-base/
 */
add_filter(
	'git_updater_plugin_config',
	function ( $config ) {
		$config['council-debt-counters/council-debt-counters.php'] = array(
			'slug'       => 'council-debt-counters',
			'repo'       => 'council-debt-counters',
			'owner'      => 'mikerouse', // Change to your GitHub username or org
			'branch'     => 'main', // Or 'master' or your default branch
			'uri'        => 'https://github.com/mikerouse/council-debt-counters',
			'remote_url' => 'https://github.com/mikerouse/council-debt-counters.git',
			'type'       => 'github',
		);
		return $config;
	}
);
