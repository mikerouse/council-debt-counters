<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CouncilDebtCounters\Docs_Manager;
use CouncilDebtCounters\Council_Admin_Page;

class Settings_Page {

	const FONT_CHOICES = array( 'Oswald', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Source Sans Pro' );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function add_menu() {
		add_menu_page(
			__( 'Council Debt Counters', 'council-debt-counters' ),
			__( 'Debt Counters', 'council-debt-counters' ),
			'manage_options',
			'council-debt-counters',
			array( __CLASS__, 'render_page' ),
			'dashicons-chart-line'
		);

		add_submenu_page(
			'council-debt-counters',
			__( 'Licence Keys and Addons', 'council-debt-counters' ),
			__( 'Licence Keys and Addons', 'council-debt-counters' ),
			'manage_options',
			'cdc-license-keys',
			array( __CLASS__, 'render_license_page' )
		);

		add_submenu_page(
			'council-debt-counters',
			__( 'Settings', 'council-debt-counters' ),
			__( 'Settings', 'council-debt-counters' ),
			'manage_options',
			'cdc-settings',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'council-debt-counters',
			__( 'Import & Export', 'council-debt-counters' ),
			__( 'Import & Export', 'council-debt-counters' ),
			'manage_options',
			'cdc-import-export',
			array( __CLASS__, 'render_import_export_page' )
		);

		// Only add unique submenus here (do not duplicate "Councils")
		add_submenu_page(
			'council-debt-counters',
			__( 'Manage Documents', 'council-debt-counters' ),
			__( 'Manage Documents', 'council-debt-counters' ),
			'manage_options',
			'cdc-manage-docs',
			array( __CLASS__, 'render_docs_page' )
		);
		add_submenu_page(
			'council-debt-counters',
			__( 'Troubleshooting', 'council-debt-counters' ),
			__( 'Troubleshooting', 'council-debt-counters' ),
			'manage_options',
			'cdc-troubleshooting',
			array( __CLASS__, 'render_troubleshooting_page' )
		);
	}

	public static function register_settings() {
		// Options on the Licence page.
		register_setting( 'cdc_license', License_Manager::OPTION_KEY );
		register_setting( 'cdc_license', License_Manager::OPTION_VALID );
		register_setting( 'cdc_license', 'cdc_openai_api_key' );
		register_setting( 'cdc_license', 'cdc_recaptcha_site_key' );
		register_setting( 'cdc_license', 'cdc_recaptcha_secret_key' );

		// Options on the Settings page.
		register_setting(
			'cdc_settings',
			'cdc_openai_model',
			array(
				'type'    => 'string',
				'default' => 'gpt-3.5-turbo',
			)
		);
                register_setting(
                        'cdc_settings',
                        'cdc_enabled_counters',
                        array(
                                'type'    => 'array',
                                'default' => array(),
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_counter_titles',
                        array(
                                'type'              => 'array',
                                'default'           => array(),
                                'sanitize_callback' => array( __CLASS__, 'sanitize_titles' ),
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_total_counter_titles',
                        array(
                                'type'              => 'array',
                                'default'           => array(),
                                'sanitize_callback' => array( __CLASS__, 'sanitize_titles' ),
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_log_level',
                        array(
                                'type'              => 'string',
				'default'           => 'standard',
				'sanitize_callback' => array( __CLASS__, 'sanitize_log_level' ),
			)
		);
		register_setting(
			'cdc_settings',
			'cdc_counter_font',
			array(
				'type'              => 'string',
				'default'           => 'Oswald',
				'sanitize_callback' => array( __CLASS__, 'sanitize_font' ),
			)
		);
                register_setting(
                        'cdc_settings',
                        'cdc_counter_weight',
                        array(
                                'type'              => 'string',
                                'default'           => '600',
                                'sanitize_callback' => array( __CLASS__, 'sanitize_weight' ),
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_default_sharing_thumbnail',
                        array(
                                'type'              => 'integer',
                                'default'           => 0,
                                'sanitize_callback' => 'absint',
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_use_cdn_assets',
                        array(
                                'type'    => 'boolean',
                                'default' => 0,
                        )
                );
        }

        public static function enqueue_assets( $hook ) {
                $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';

                if ( 'debt-counters_page_cdc-import-export' === $hook ) {
                        wp_enqueue_script(
                                'cdc-import-csv',
                                plugins_url( 'admin/js/import-csv.js', $plugin_file ),
                                array(),
                                '0.1.0',
                                true
                        );
                        wp_enqueue_style( 'cdc-import-progress', plugins_url( 'admin/css/import-progress.css', $plugin_file ), array(), '0.1.0' );
                        wp_localize_script(
                                'cdc-import-csv',
                                'cdcImport',
                                array(
                                        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                                        'nonce'    => wp_create_nonce( 'cdc_import_csv_row' ),
                                        'progress' => __( 'Imported %1 of %2 rows…', 'council-debt-counters' ),
                                        'done'     => __( 'Import complete', 'council-debt-counters' ),
                                )
                        );
                }

                if ( 'debt-counters_page_cdc-settings' === $hook ) {
                        wp_enqueue_media();
                        wp_enqueue_script(
                                'cdc-media-select',
                                plugins_url( 'admin/js/media-select.js', $plugin_file ),
                                array(),
                                '0.1.0',
                                true
                        );
                        wp_localize_script(
                                'cdc-media-select',
                                'CDC_MEDIA_SELECT',
                                array(
                                        'title'  => __( 'Select Image', 'council-debt-counters' ),
                                        'button' => __( 'Use this image', 'council-debt-counters' ),
                                )
                        );
                }
        }

	public static function sanitize_log_level( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'verbose', 'standard', 'quiet' ), true ) ? $value : 'standard';
	}

	public static function sanitize_font( $value ) {
		$value = sanitize_text_field( $value );
		return in_array( $value, self::FONT_CHOICES, true ) ? $value : 'Oswald';
	}

        public static function sanitize_weight( $value ) {
                $value = preg_replace( '/[^0-9]/', '', $value );
                if ( $value < 100 || $value > 900 ) {
                        return '600';
                }
                return $value;
        }

        public static function sanitize_titles( $value ) {
                if ( ! is_array( $value ) ) {
                        return array();
                }
                $clean = array();
                foreach ( $value as $k => $v ) {
                        $clean[ sanitize_key( $k ) ] = sanitize_text_field( $v );
                }
                return $clean;
        }

	public static function render_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/instructions-page.php';
	}

	public static function render_license_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/license-page.php';
	}

	public static function render_settings_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/settings-page.php';
	}

	public static function render_import_export_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/import-export-page.php';
	}

	public static function render_docs_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/docs-manager-page.php';
	}

	public static function render_troubleshooting_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/troubleshooting-page.php';
	}
}
