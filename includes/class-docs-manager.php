<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Docs_Manager {
    const DOCS_DIR = 'docs';
    const ALLOWED_EXTENSIONS = ['csv', 'pdf', 'xlsx'];
    const FREE_LIMIT = 10;

    const TABLE = 'cdc_documents';
    const DOC_TYPES = ['statement_of_accounts'];

    /**
     * Return a list of financial years including the current year and
     * the previous $count years.
     */
    public static function financial_years( int $count = 10 ) {
        $current = self::current_financial_year();
        list( $start, $end ) = explode( '/', $current );
        $start = (int) $start;
        $years = [];
        for ( $i = 0; $i <= $count; $i++ ) {
            $y = $start - $i;
            $years[] = sprintf( '%d/%02d', $y, ( $y + 1 ) % 100 );
        }
        return $years;
    }

    public static function current_financial_year() {
        $year = (int) date( 'Y' );
        $start = ( date( 'n' ) < 4 ) ? $year - 1 : $year;
        $end = $start + 1;
        return sprintf( '%d/%02d', $start, $end % 100 );
    }

    public static function init() {
        add_action( 'init', [ __CLASS__, 'maybe_install' ] );
        add_action( 'admin_post_cdc_confirm_ai_figures', [ __CLASS__, 'handle_confirm_ai' ] );
        add_action( 'admin_post_cdc_dismiss_ai_figures', [ __CLASS__, 'handle_dismiss_ai' ] );
        add_action( 'admin_notices', [ __CLASS__, 'show_ai_suggestions' ] );
        add_action( 'wp_ajax_cdc_extract_figures', [ __CLASS__, 'handle_ajax_extract' ] );
    }

    public static function install() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            doc_type varchar(100) NOT NULL,
            council_id bigint(20) NOT NULL DEFAULT 0,
            financial_year varchar(9) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY filename (filename)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function maybe_install() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            self::install();
        } else {
            $columns = $wpdb->get_col( "DESC $table", 0 );
            if ( ! in_array( 'financial_year', $columns, true ) ) {
                $wpdb->query( "ALTER TABLE $table ADD financial_year varchar(9) NOT NULL DEFAULT '" . self::current_financial_year() . "'" );
            }
        }
    }

    public static function get_docs_path() {
        return plugin_dir_path( dirname( __FILE__ ) ) . self::DOCS_DIR . '/';
    }

    public static function list_documents( int $council_id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if ( $council_id > 0 ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE council_id = %d ORDER BY financial_year DESC", $council_id ) );
        }
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY financial_year DESC" );
    }

    public static function list_orphan_documents() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_results( "SELECT * FROM $table WHERE council_id = 0" );
    }

    public static function can_upload() {
        $is_pro = License_Manager::is_valid();
        if ( $is_pro ) return true;
        return count( self::list_documents() ) < self::FREE_LIMIT;
    }

    public static function upload_document( $file, string $doc_type = '', int $council_id = 0, string $financial_year = '' ) {
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS ) ) {
            Error_Logger::log( 'Attempted upload of invalid file type: ' . $file['name'] );
            return __( 'Invalid file type. Only XLSX, CSV, and PDF are allowed.', 'council-debt-counters' );
        }
        if ( ! self::can_upload() ) {
            Error_Logger::log( 'Document upload blocked - free limit reached' );
            return __( 'Free version limit reached. Upgrade to Pro for unlimited documents.', 'council-debt-counters' );
        }
        $filename = basename( $file['name'] );
        $target   = self::get_docs_path() . $filename;
        if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
            self::add_document( $filename, $doc_type, $council_id, $financial_year );
            Error_Logger::log_info( 'Document uploaded: ' . $filename );

            if ( $doc_type === 'statement_of_accounts' && $council_id > 0 ) {
                self::maybe_extract_figures( $target, $council_id );
            }

            return true;
        }
        Error_Logger::log( 'Failed to move uploaded document: ' . $file['name'] );
        return __( 'Upload failed.', 'council-debt-counters' );
    }

    public static function delete_document( $filename ) {
        $file = self::get_docs_path() . $filename;
        if ( file_exists( $file ) ) {
            unlink( $file );
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . self::TABLE, [ 'filename' => $filename ], [ '%s' ] );
            Error_Logger::log_info( 'Document deleted: ' . $filename );
            return true;
        }
        return false;
    }

    /**
     * Download a PDF from a remote URL into the docs directory.
     *
     * @param string $url Remote file URL.
     * @return true|string True on success or error message.
     */
    public static function import_from_url( string $url, string $doc_type = '', int $council_id = 0, string $financial_year = '' ) {
        $path = parse_url( $url, PHP_URL_PATH );
        $ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS ) ) {
            Error_Logger::log( 'Invalid import file type: ' . $url );
            return __( 'Invalid file type. Only XLSX, CSV, and PDF are allowed.', 'council-debt-counters' );
        }

        if ( ! self::can_upload() ) {
            Error_Logger::log( 'Document import blocked - free limit reached' );
            return __( 'Free version limit reached. Upgrade to Pro for unlimited documents.', 'council-debt-counters' );
        }

        if ( ! function_exists( 'download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            Error_Logger::log( 'Failed to download document: ' . $url . ' - ' . $tmp->get_error_message() );
            return __( 'Download failed.', 'council-debt-counters' );
        }

        $filename = basename( $path );
        $target   = self::get_docs_path() . $filename;
        if ( ! copy( $tmp, $target ) ) {
            unlink( $tmp );
            Error_Logger::log( 'Failed to copy imported document to docs: ' . $filename );
            return __( 'Import failed.', 'council-debt-counters' );
        }
        unlink( $tmp );
        self::add_document( $filename, $doc_type, $council_id, $financial_year );
        return true;
    }

    public static function get_document( string $filename ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE filename = %s", $filename ) );
    }

    public static function get_document_by_id( int $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE id = %d", $id ) );
    }

    public static function add_document( string $filename, string $doc_type = '', int $council_id = 0, string $financial_year = '' ) {
        if ( empty( $financial_year ) ) {
            $financial_year = self::current_financial_year();
        }
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE, [
            'filename'   => $filename,
            'doc_type'   => $doc_type,
            'council_id' => $council_id,
            'financial_year' => $financial_year,
        ], [ '%s', '%s', '%d', '%s' ] );
    }

    public static function assign_document( string $filename, int $council_id, string $doc_type, string $financial_year = '' ) {
        if ( ! in_array( $doc_type, self::DOC_TYPES, true ) ) {
            return false;
        }
        global $wpdb;
        $doc = self::get_document( $filename );
        if ( $doc ) {
            $wpdb->update( $wpdb->prefix . self::TABLE, [
                'council_id' => $council_id,
                'doc_type'   => $doc_type,
                'financial_year' => $financial_year ?: $doc->financial_year,
            ], [ 'id' => $doc->id ], [ '%d', '%s', '%s' ], [ '%d' ] );
        } else {
            self::add_document( $filename, $doc_type, $council_id, $financial_year );
        }
        Error_Logger::log_info( 'Document assigned: ' . $filename . ' to council ' . $council_id );

        if ( $doc_type === 'statement_of_accounts' && $council_id > 0 ) {
            $path = self::get_docs_path() . $filename;
            self::maybe_extract_figures( $path, $council_id );
        }

        return true;
    }

    /**
     * Update document details.
     */
    public static function update_document( int $id, array $data ) {
        global $wpdb;
        $fields = [];
        if ( isset( $data['doc_type'] ) ) {
            $fields['doc_type'] = sanitize_key( $data['doc_type'] );
        }
        if ( isset( $data['financial_year'] ) ) {
            $fields['financial_year'] = sanitize_text_field( $data['financial_year'] );
        }
        if ( empty( $fields ) ) {
            return false;
        }
        $wpdb->update( $wpdb->prefix . self::TABLE, $fields, [ 'id' => $id ], [ '%s', '%s' ], [ '%d' ] );
        return true;
    }

    private static function maybe_extract_figures( string $file, int $council_id ) {
        $text = self::extract_text( $file );
        if ( empty( $text ) ) {
            $error = new \WP_Error( 'no_text', __( 'Failed to read document.', 'council-debt-counters' ) );
            Error_Logger::log( 'AI extraction error: ' . $error->get_error_message() );
            return $error;
        }
        $data = AI_Extractor::extract_key_figures( $text );
        if ( is_wp_error( $data ) ) {
            Error_Logger::log( 'AI extraction error: ' . $data->get_error_message() );
            return $data;
        }
        if ( is_array( $data ) ) {
            self::store_ai_suggestions( $council_id, $data );
            return $data;
        }
        $error = new \WP_Error( 'invalid_ai_data', __( 'Invalid AI response.', 'council-debt-counters' ) );
        Error_Logger::log( 'AI extraction error: unexpected data' );
        return $error;
    }

    private static function store_ai_suggestions( int $council_id, array $data ) {
        $all = get_option( 'cdc_ai_suggestions', [] );
        $all[ $council_id ] = $data;
        update_option( 'cdc_ai_suggestions', $all );
    }

    public static function show_ai_suggestions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $all = get_option( 'cdc_ai_suggestions', [] );
        if ( empty( $all ) ) {
            return;
        }
        foreach ( $all as $cid => $data ) {
            $name = get_the_title( $cid );
            echo '<div class="notice notice-info"><p>';
            printf( esc_html__( 'OpenAI suggested figures for %s. Review and confirm below.', 'council-debt-counters' ), esc_html( $name ) );
            echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="cdc_confirm_ai_figures" />';
            echo '<input type="hidden" name="council_id" value="' . esc_attr( $cid ) . '" />';
            wp_nonce_field( 'cdc_confirm_ai_figures' );
            echo '<table class="form-table">';
            foreach ( $data as $field => $value ) {
                echo '<tr><th>' . esc_html( $field ) . '</th><td><input type="text" name="figures[' . esc_attr( $field ) . ']" value="' . esc_attr( $value ) . '" /></td></tr>';
            }
            echo '</table>';
            submit_button( __( 'Save Figures', 'council-debt-counters' ) );
            echo '</form>';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:6px;">';
            echo '<input type="hidden" name="action" value="cdc_dismiss_ai_figures" />';
            echo '<input type="hidden" name="council_id" value="' . esc_attr( $cid ) . '" />';
            wp_nonce_field( 'cdc_dismiss_ai_figures' );
            submit_button( __( 'Dismiss', 'council-debt-counters' ), 'secondary' );
            echo '</form></div>';
        }
    }

    public static function handle_confirm_ai() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_confirm_ai_figures' );
        $cid = intval( $_POST['council_id'] );
        $figures = (array) ( $_POST['figures'] ?? [] );
        foreach ( $figures as $field => $value ) {
            Custom_Fields::update_value( $cid, sanitize_key( $field ), sanitize_text_field( $value ) );
        }
        $all = get_option( 'cdc_ai_suggestions', [] );
        unset( $all[ $cid ] );
        update_option( 'cdc_ai_suggestions', $all );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_dismiss_ai() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_dismiss_ai_figures' );
        $cid = intval( $_POST['council_id'] );
        $all = get_option( 'cdc_ai_suggestions', [] );
        unset( $all[ $cid ] );
        update_option( 'cdc_ai_suggestions', $all );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_ajax_extract() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ] );
        }

        if ( ! License_Manager::is_valid() ) {
            $prefix = substr( License_Manager::get_license_key(), 0, 8 );
            Error_Logger::log( 'AI extraction blocked: license invalid (key ' . $prefix . '…)');
            wp_send_json_error( [ 'message' => __( 'AI features require a Pro license.', 'council-debt-counters' ) ] );
        }

        if ( ! get_option( 'cdc_openai_api_key' ) ) {
            Error_Logger::log( 'AI extraction blocked: API key missing' );
            wp_send_json_error( [ 'message' => __( 'OpenAI API key not configured.', 'council-debt-counters' ) ] );
        }

        $doc_id = intval( $_POST['doc_id'] ?? 0 );
        $doc    = $doc_id ? self::get_document_by_id( $doc_id ) : null;
        if ( ! $doc ) {
            Error_Logger::log( 'AI extraction failed: document not found #' . $doc_id );
            wp_send_json_error( [ 'message' => __( 'Document not found.', 'council-debt-counters' ) ] );
        }

        if ( $doc->doc_type !== 'statement_of_accounts' || $doc->council_id <= 0 ) {
            Error_Logger::log( 'AI extraction failed: invalid document #' . $doc_id );
            wp_send_json_error( [ 'message' => __( 'Invalid document.', 'council-debt-counters' ) ] );
        }

        $path   = self::get_docs_path() . $doc->filename;
        $result = self::maybe_extract_figures( $path, (int) $doc->council_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Extraction failed: %s', 'council-debt-counters' ), $result->get_error_message() ) ] );
        }

        wp_send_json_success( [ 'message' => __( 'Extraction complete. Review suggestions below.', 'council-debt-counters' ) ] );
    }

    private static function extract_text( string $file ) {
        $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        if ( $ext === 'pdf' && class_exists( '\\Smalot\\PdfParser\\Parser' ) ) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile( $file );
                return $pdf->getText();
            } catch ( \Exception $e ) {
                Error_Logger::log( 'PDF parse error: ' . $e->getMessage() );
                return '';
            }
        }
        if ( $ext === 'csv' || $ext === 'txt' ) {
            return file_get_contents( $file );
        }
        return '';
    }
}
