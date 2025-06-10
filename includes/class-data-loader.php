<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Data_Loader {
    /**
     * Register hooks for CLI and admin import handling.
     */
    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'handle_admin_action' ] );
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'cdc load_csv', [ __CLASS__, 'cli_load_csv' ] );
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
                wp_update_post( [ 'ID' => $post_id, 'post_title' => $name ] );
            } else {
                $post_id = wp_insert_post( [
                    'post_title'  => $name,
                    'post_type'   => 'council',
                    'post_status' => 'publish',
                ] );
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
                if ( function_exists( 'update_field' ) ) {
                    update_field( $field, $value, $post_id );
                } else {
                    update_post_meta( $post_id, $field, $value );
                }
            }

            if ( method_exists( '\\CouncilDebtCounters\\Council_Post_Type', 'calculate_total_debt' ) ) {
                Council_Post_Type::calculate_total_debt( $post_id );
            }

            $count++;
        }
        fclose( $handle );
        Error_Logger::log_info( 'Imported councils from CSV: ' . $path );
        return $count;
    }

    /**
     * Handle CLI command to load CSV.
     */
    public static function cli_load_csv( $args, $assoc_args ) {
        $path = $args[0] ?? '';
        $result = self::load_csv( $path );
        if ( is_wp_error( $result ) ) {
            \WP_CLI::error( $result->get_error_message() );
        }
        \WP_CLI::success( sprintf( __( 'Imported %d councils.', 'council-debt-counters' ), $result ) );
    }

    /**
     * Handle admin CSV upload action.
     */
    public static function handle_admin_action() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( empty( $_FILES['cdc_csv_file']['tmp_name'] ) ) {
            return;
        }
        check_admin_referer( 'cdc_load_csv', 'cdc_load_csv_nonce' );
        $result = self::load_csv( $_FILES['cdc_csv_file']['tmp_name'] );
        add_action( 'admin_notices', function() use ( $result ) {
            if ( is_wp_error( $result ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Imported %d councils.', 'council-debt-counters' ), $result ) ) . '</p></div>';
            }
        } );
    }
}

