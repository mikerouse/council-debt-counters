<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data_Loader {
	/**
	 * Register hooks for CLI and admin import handling.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_action' ) );
		add_action( 'wp_ajax_cdc_import_row', array( __CLASS__, 'ajax_import_row' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'cdc load_csv', array( __CLASS__, 'cli_load_csv' ) );
			\WP_CLI::add_command( 'cdc load_json', array( __CLASS__, 'cli_load_json' ) );
		}
	}

	/**
	 * Parse a CSV file and create or update council posts.
	 *
	 * @param string $path Path to the CSV file.
	 * @return int|\WP_Error Number of imported rows or WP_Error on failure.
	 */
	public static function load_csv( string $path ) {
		if ( ! file_exists( $path ) ) {
			Error_Logger::log( 'CSV not found: ' . $path );
			return new \WP_Error( 'csv_missing', __( 'CSV file not found.', 'council-debt-counters' ) );
		}

		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			Error_Logger::log( 'Unable to open CSV: ' . $path );
			return new \WP_Error( 'csv_open_failed', __( 'Unable to open CSV file.', 'council-debt-counters' ) );
		}

		$header = fgetcsv( $handle );
		if ( empty( $header ) ) {
			fclose( $handle );
			Error_Logger::log( 'CSV header missing: ' . $path );
			return new \WP_Error( 'csv_header', __( 'CSV header missing.', 'council-debt-counters' ) );
		}

		$count = 0;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$data = array_combine( $header, $row );
			if ( false === $data ) {
				Error_Logger::log( 'Column mismatch in CSV: ' . $path );
				continue;
			}
			if ( empty( $data['council_name'] ) ) {
				Error_Logger::log( 'Missing council_name in CSV row' );
				continue;
			}

			$name    = sanitize_text_field( $data['council_name'] );
			$post    = get_page_by_title( $name, OBJECT, 'council' );
			$post_id = $post ? $post->ID : 0;

			if ( $post_id ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $name,
					)
				);
			} else {
				$post_id = wp_insert_post(
					array(
						'post_title'  => $name,
						'post_type'   => 'council',
						'post_status' => 'draft',
					)
				);
				if ( is_wp_error( $post_id ) ) {
					Error_Logger::log( 'Failed to insert council: ' . $name );
					continue;
				}
			}

                        foreach ( $data as $field => $value ) {
                                if ( 'council_name' === $field ) {
                                        continue;
                                }
                                if ( '' === $value ) {
                                        continue;
                                }
                                $info = \CouncilDebtCounters\Custom_Fields::get_field_by_name( $field );
                                if ( $info && in_array( $info->type, array( 'number', 'money' ), true ) ) {
                                        $value = str_replace( ',', '', $value );
                                }
                                \CouncilDebtCounters\Custom_Fields::update_value( $post_id, $field, $value, CDC_Utils::current_financial_year() );
                        }

			if ( method_exists( '\\CouncilDebtCounters\\Council_Post_Type', 'calculate_total_debt' ) ) {
				Council_Post_Type::calculate_total_debt( $post_id );
			}

			++$count;
		}
		fclose( $handle );
		Error_Logger::log_info( 'Imported councils from CSV: ' . $path );
		return $count;
	}

	/**
	 * Parse a JSON file and create or update council posts.
	 *
	 * @param string $path Path to JSON file.
	 * @return int|\WP_Error Number of imported rows or WP_Error on failure.
	 */
	public static function load_json( string $path ) {
		if ( ! file_exists( $path ) ) {
			return new \WP_Error( 'json_missing', __( 'JSON file not found.', 'council-debt-counters' ) );
		}
		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return new \WP_Error( 'json_read', __( 'Unable to read JSON file.', 'council-debt-counters' ) );
		}
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'json_invalid', __( 'Invalid JSON.', 'council-debt-counters' ) );
		}
		$count = 0;
		foreach ( $data as $row ) {
			if ( empty( $row['council_name'] ) ) {
				continue;
			}
			$name    = sanitize_text_field( $row['council_name'] );
			$post    = get_page_by_title( $name, OBJECT, 'council' );
			$post_id = $post ? $post->ID : 0;

			if ( $post_id ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $name,
					)
				);
			} else {
				$post_id = wp_insert_post(
					array(
						'post_title'  => $name,
						'post_type'   => 'council',
						'post_status' => 'publish',
					)
				);
				if ( is_wp_error( $post_id ) ) {
					continue;
				}
			}

			foreach ( $row as $field => $value ) {
				if ( 'council_name' === $field ) {
					continue;
				}
				if ( '' === $value ) {
					continue;
				}
                                Custom_Fields::update_value( $post_id, $field, $value, CDC_Utils::current_financial_year() );
			}

			if ( method_exists( '\\CouncilDebtCounters\\Council_Post_Type', 'calculate_total_debt' ) ) {
				Council_Post_Type::calculate_total_debt( $post_id );
			}

			++$count;
		}
		Error_Logger::log_info( 'Imported councils from JSON: ' . $path );
		return $count;
	}

	/**
	 * Handle CLI command to load CSV.
	 */
	public static function cli_load_csv( $args, $assoc_args ) {
		$path   = $args[0] ?? '';
		$result = self::load_csv( $path );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( sprintf( __( 'Imported %d councils.', 'council-debt-counters' ), $result ) );
	}

	public static function cli_load_json( $args, $assoc_args ) {
		$path   = $args[0] ?? '';
		$result = self::load_json( $path );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( sprintf( __( 'Imported %d councils.', 'council-debt-counters' ), $result ) );
	}

	/**
	 * Export all councils as JSON or CSV.
	 */
	public static function export_data( string $format = 'csv' ) {
		$councils = get_posts(
			array(
				'post_type'   => 'council',
				'numberposts' => -1,
			)
		);
		$fields   = Custom_Fields::get_fields();
		$rows     = array();
		foreach ( $councils as $c ) {
			$row = array( 'council_name' => $c->post_title );
			foreach ( $fields as $f ) {
				if ( 'council_name' === $f->name ) {
					continue;
				}
                                $row[ $f->name ] = Custom_Fields::get_value( $c->ID, $f->name, CDC_Utils::current_financial_year() );
			}
			$rows[] = $row;
		}
		if ( 'json' === $format ) {
			return wp_json_encode( $rows );
		}
		if ( empty( $rows ) ) {
			return '';
		}
		$fh = fopen( 'php://temp', 'r+' );
		fputcsv( $fh, array_keys( $rows[0] ) );
		foreach ( $rows as $r ) {
			fputcsv( $fh, $r );
		}
		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );
		return $csv;
	}

	/**
	 * Export plugin settings and keys as JSON.
	 */
	public static function export_settings() {
		$options = array(
			License_Manager::OPTION_KEY   => get_option( License_Manager::OPTION_KEY, '' ),
			License_Manager::OPTION_VALID => get_option( License_Manager::OPTION_VALID, '' ),
			'cdc_openai_api_key'          => get_option( 'cdc_openai_api_key', '' ),
			'cdc_recaptcha_site_key'      => get_option( 'cdc_recaptcha_site_key', '' ),
			'cdc_recaptcha_secret_key'    => get_option( 'cdc_recaptcha_secret_key', '' ),
			'cdc_openai_model'            => get_option( 'cdc_openai_model', 'gpt-3.5-turbo' ),
                        'cdc_enabled_counters'        => get_option( 'cdc_enabled_counters', array() ),
                       'cdc_counter_titles'          => get_option( 'cdc_counter_titles', array() ),
                       'cdc_total_counter_titles'    => get_option( 'cdc_total_counter_titles', array() ),
                       'cdc_log_level'               => get_option( 'cdc_log_level', 'standard' ),
               );
		return wp_json_encode( $options );
	}

	/**
	 * Import plugin settings from a JSON file.
	 *
	 * @param string $path Path to the JSON file.
	 * @return true|\WP_Error
	 */
	public static function import_settings( string $path ) {
		if ( ! file_exists( $path ) ) {
			return new \WP_Error( 'settings_missing', __( 'Settings file not found.', 'council-debt-counters' ) );
		}
		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return new \WP_Error( 'settings_read', __( 'Unable to read settings file.', 'council-debt-counters' ) );
		}
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'settings_invalid', __( 'Invalid JSON.', 'council-debt-counters' ) );
		}
		foreach ( $data as $option => $value ) {
			update_option( $option, $value );
		}
		return true;
	}

	/**
	 * Handle admin CSV upload action.
	 */
	public static function handle_admin_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['cdc_export_settings'] ) ) {
			check_admin_referer( 'cdc_export_settings', 'cdc_export_settings_nonce' );
			$data = self::export_settings();
			Error_Logger::log_info( 'Exported plugin settings' );
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename=cdc-settings.json' );
			echo $data;
			exit;
		}
		if ( isset( $_FILES['cdc_settings_file']['tmp_name'] ) ) {
			check_admin_referer( 'cdc_import_settings', 'cdc_import_settings_nonce' );
			$file   = $_FILES['cdc_settings_file']['tmp_name'];
			$result = self::import_settings( $file );
			add_action(
				'admin_notices',
				function () use ( $result ) {
					if ( is_wp_error( $result ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
					} else {
						echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings imported.', 'council-debt-counters' ) . '</p></div>';
					}
				}
			);
			return;
		}
		if ( isset( $_POST['cdc_export'] ) ) {
			check_admin_referer( 'cdc_export', 'cdc_export_nonce' );
			$format = sanitize_key( $_POST['format'] ?? 'csv' );
			$data   = self::export_data( $format );
			Error_Logger::log_info( 'Exported councils as ' . $format );
			if ( 'json' === $format ) {
				header( 'Content-Type: application/json' );
				header( 'Content-Disposition: attachment; filename=councils.json' );
			} else {
				header( 'Content-Type: text/csv' );
				header( 'Content-Disposition: attachment; filename=councils.csv' );
			}
			echo $data;
			exit;
		}
		if ( empty( $_FILES['cdc_csv_file']['tmp_name'] ) ) {
			return;
		}
		check_admin_referer( 'cdc_load_csv', 'cdc_load_csv_nonce' );
		$file = $_FILES['cdc_csv_file']['tmp_name'];
		$ext  = pathinfo( $_FILES['cdc_csv_file']['name'], PATHINFO_EXTENSION );
		if ( strtolower( $ext ) === 'json' ) {
			$result = self::load_json( $file );
		} else {
			$result = self::load_csv( $file );
		}
		add_action(
			'admin_notices',
			function () use ( $result ) {
				if ( is_wp_error( $result ) ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
				} else {
					echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Imported %d councils.', 'council-debt-counters' ), $result ) ) . '</p></div>';
				}
			}
		);
	}

	/**
	 * AJAX handler for importing a single CSV row.
	 */
	public static function ajax_import_row() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ) );
		}
		check_ajax_referer( 'cdc_import_csv_row', 'nonce' );
		$row = json_decode( wp_unslash( $_POST['row'] ?? '' ), true );
		if ( ! is_array( $row ) || empty( $row['council_name'] ) ) {
			wp_send_json_error( __( 'Invalid row.', 'council-debt-counters' ) );
		}

		$name    = sanitize_text_field( $row['council_name'] );
		$post    = get_page_by_title( $name, OBJECT, 'council' );
		$post_id = $post ? $post->ID : 0;

		if ( $post_id ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $name,
				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $name,
					'post_type'   => 'council',
					'post_status' => 'draft',
				)
			);
			if ( is_wp_error( $post_id ) ) {
				wp_send_json_error( __( 'Insert failed.', 'council-debt-counters' ) );
			}
		}

                foreach ( $row as $field => $value ) {
                        if ( 'council_name' === $field || $value === '' ) {
                                continue;
                        }
                        $info = Custom_Fields::get_field_by_name( $field );
                        if ( $info && in_array( $info->type, array( 'number', 'money' ), true ) ) {
                                $value = str_replace( ',', '', $value );
                        }
                        Custom_Fields::update_value( $post_id, $field, $value, CDC_Utils::current_financial_year() );
                }

		if ( method_exists( '\\CouncilDebtCounters\\Council_Post_Type', 'calculate_total_debt' ) ) {
			Council_Post_Type::calculate_total_debt( $post_id );
		}

		wp_send_json_success( array( 'id' => $post_id ) );
	}
}
