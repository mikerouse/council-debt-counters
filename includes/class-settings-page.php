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
                add_action( 'wp_ajax_cdc_add_year', array( __CLASS__, 'ajax_add_year' ) );
                add_action( 'wp_ajax_cdc_delete_year', array( __CLASS__, 'ajax_delete_year' ) );
                add_action( 'wp_ajax_cdc_update_year', array( __CLASS__, 'ajax_update_year' ) );
                add_action( 'wp_ajax_cdc_set_default_year', array( __CLASS__, 'ajax_set_default_year' ) );
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
                // Options on the Settings page.
                register_setting( 'cdc_settings', 'cdc_openai_api_key' );
                register_setting( 'cdc_settings', 'cdc_recaptcha_site_key' );
                register_setting( 'cdc_settings', 'cdc_recaptcha_secret_key' );
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
                        'cdc_financial_years',
                        array(
                                'type'              => 'array',
                                'default'           => \CouncilDebtCounters\Docs_Manager::default_years(),
                                'sanitize_callback' => array( __CLASS__, 'sanitize_year_list' ),
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
                        'cdc_total_counter_years',
                        array(
                                'type'              => 'array',
                                'default'           => array(
                                        'debt'        => '2023/24',
                                        'spending'    => '2023/24',
                                        'income'      => '2023/24',
                                        'deficit'     => '2023/24',
                                        'interest'    => '2023/24',
                                        'reserves'    => '2023/24',
                                        'consultancy' => '2023/24',
                                ),
                                'sanitize_callback' => array( __CLASS__, 'sanitize_years' ),
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
                        'cdc_counter_duration',
                        array(
                                'type'              => 'integer',
                                'default'           => 15,
                                'sanitize_callback' => array( __CLASS__, 'sanitize_duration' ),
                        )
                );
                register_setting(
                        'cdc_settings',
                        'cdc_default_financial_year',
                        array(
                                'type'    => 'string',
                                'default' => '2023/24',
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
                register_setting(
                        'cdc_settings',
                        'cdc_blocked_ips',
                        array(
                                'type'              => 'string',
                                'sanitize_callback' => array( __CLASS__, 'sanitize_blocked_ips' ),
                                'default'           => '',
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
                        wp_enqueue_script(
                                'cdc-years',
                                plugins_url( 'admin/js/years.js', $plugin_file ),
                                array(),
                                '0.1.0',
                                true
                        );
                        wp_enqueue_style( 'cdc-years-progress', plugins_url( 'admin/css/years-progress.css', $plugin_file ), array(), '0.1.0' );
                        wp_localize_script(
                                'cdc-years',
                                'cdcYears',
                                array(
                                        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                                        'nonce'         => wp_create_nonce( 'cdc_manage_years' ),
                                        'deleteConfirm' => __( 'Delete this year?', 'council-debt-counters' ),
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

        public static function sanitize_duration( $value ) {
                $value = (int) $value;
                if ( $value < 1 ) {
                        return 15;
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

        public static function sanitize_years( $value ) {
                if ( ! is_array( $value ) ) {
                        return array();
                }
                $clean = array();
                foreach ( $value as $k => $v ) {
                        $year = sanitize_text_field( $v );
                        if ( ! preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
                                $year = '2023/24';
                        }
                        $clean[ sanitize_key( $k ) ] = $year;
                }
                return $clean;
        }

        /**
         * Sanitize an array of year strings.
         */
        public static function sanitize_year_list( $value ) {
                if ( ! is_array( $value ) ) {
                        return array();
                }
                $clean = array();
                foreach ( $value as $v ) {
                        $year = sanitize_text_field( $v );
                        if ( preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
                                $clean[] = $year;
                        }
                }
                $clean = array_unique( $clean );
                rsort( $clean );
                return array_values( $clean );
        }

        public static function sanitize_blocked_ips( $value ) {
                $lines = explode( "\n", (string) $value );
                $clean = array();
                foreach ( $lines as $line ) {
                        $line = trim( $line );
                        if ( $line !== '' ) {
                                $clean[] = $line;
                        }
                }
                return implode( "\n", $clean );
        }

        /* ******** AJAX handlers for managing years ******** */
        public static function ajax_add_year() {
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
                }
                check_ajax_referer( 'cdc_manage_years', 'nonce' );
                $year  = sanitize_text_field( $_POST['year'] ?? '' );
                if ( ! preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
                        wp_send_json_error();
                }
                $years = (array) get_option( 'cdc_financial_years', [] );
                if ( ! in_array( $year, $years, true ) ) {
                        $years[] = $year;
                        $years   = self::sanitize_year_list( $years );
                        update_option( 'cdc_financial_years', $years );
                }
                wp_send_json_success();
        }

        public static function ajax_delete_year() {
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
                }
                check_ajax_referer( 'cdc_manage_years', 'nonce' );
                $year = sanitize_text_field( $_POST['year'] ?? '' );
                $years = (array) get_option( 'cdc_financial_years', [] );
                $years = array_values( array_diff( $years, [ $year ] ) );
                update_option( 'cdc_financial_years', $years );
                if ( get_option( 'cdc_default_financial_year', '' ) === $year ) {
                        update_option( 'cdc_default_financial_year', $years[0] ?? '2023/24' );
                }
                wp_send_json_success();
        }

        public static function ajax_update_year() {
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
                }
                check_ajax_referer( 'cdc_manage_years', 'nonce' );
                $old = sanitize_text_field( $_POST['old'] ?? '' );
                $new = sanitize_text_field( $_POST['new'] ?? '' );
                if ( ! preg_match( '/^\d{4}\/\d{2}$/', $new ) ) {
                        wp_send_json_error();
                }
                $years = (array) get_option( 'cdc_financial_years', [] );
                $key   = array_search( $old, $years, true );
                if ( false === $key ) {
                        wp_send_json_error();
                }
                $years[ $key ] = $new;
                $years         = self::sanitize_year_list( $years );
                update_option( 'cdc_financial_years', $years );
                if ( get_option( 'cdc_default_financial_year', '' ) === $old ) {
                        update_option( 'cdc_default_financial_year', $new );
                }
                wp_send_json_success();
        }

        public static function ajax_set_default_year() {
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
                }
                check_ajax_referer( 'cdc_manage_years', 'nonce' );
                $year = sanitize_text_field( $_POST['year'] ?? '' );
                $years = (array) get_option( 'cdc_financial_years', [] );
                if ( ! in_array( $year, $years, true ) ) {
                        $years[] = $year;
                        $years   = self::sanitize_year_list( $years );
                        update_option( 'cdc_financial_years', $years );
                }
                update_option( 'cdc_default_financial_year', $year );
                wp_send_json_success();
        }

	public static function render_page() {
		include plugin_dir_path( __DIR__ ) . 'admin/views/instructions-page.php';
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
