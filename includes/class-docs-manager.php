<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Docs_Manager {
    const DOCS_DIR = 'docs';
    const ALLOWED_EXTENSIONS = ['csv', 'pdf', 'xlsx'];

    const TABLE = 'cdc_documents';
    const DOC_TYPES = [
        'draft_statement_of_accounts',
        'audited_statement_of_accounts',
    ];

    /**
     * Get human readable labels for doc types.
     */
    public static function doc_type_labels() {
        return [
            'draft_statement_of_accounts'   => __( 'Draft Statement', 'council-debt-counters' ),
            'audited_statement_of_accounts' => __( 'Audited Statement', 'council-debt-counters' ),
        ];
    }

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
        $default = get_option( 'cdc_default_financial_year', '' );
        if ( $default ) {
            return $default;
        }

        // Default to 2023/24 if no option is stored. This prevents the
        // front-end year selectors from jumping ahead before figures are
        // available for newer years.
        return '2023/24';
    }

    public static function init() {
        add_action( 'init', [ __CLASS__, 'maybe_install' ] );
        add_action( 'admin_post_cdc_confirm_ai_figures', [ __CLASS__, 'handle_confirm_ai' ] );
        add_action( 'admin_post_cdc_dismiss_ai_figures', [ __CLASS__, 'handle_dismiss_ai' ] );
        add_action( 'admin_notices', [ __CLASS__, 'show_ai_suggestions' ] );
        add_action( 'wp_ajax_cdc_extract_info', [ __CLASS__, 'handle_ajax_extract_info' ] );
        add_action( 'wp_ajax_cdc_extract_figures', [ __CLASS__, 'handle_ajax_extract' ] );
        add_action( 'wp_ajax_cdc_upload_doc', [ __CLASS__, 'handle_ajax_upload_doc' ] );
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

    /**
     * Ensure filename is unique within docs directory.
     */
    private static function unique_filename( string $filename ) {
        $dir = self::get_docs_path();
        if ( ! function_exists( 'wp_unique_filename' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        return wp_unique_filename( $dir, $filename );
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
        return true;
    }

    public static function upload_document( $file, string $doc_type = '', int $council_id = 0, string $financial_year = '' ) {
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS ) ) {
            Error_Logger::log( 'Attempted upload of invalid file type: ' . $file['name'] );
            return __( 'Invalid file type. Only XLSX, CSV, and PDF are allowed.', 'council-debt-counters' );
        }
        if ( ! self::can_upload() ) {
            Error_Logger::log( 'Document upload blocked - limit reached' );
            return __( 'Upload limit reached.', 'council-debt-counters' );
        }
        $filename = basename( $file['name'] );
        $filename = self::unique_filename( $filename );
        $target   = self::get_docs_path() . $filename;
        if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
            self::add_document( $filename, $doc_type, $council_id, $financial_year );
            Error_Logger::log_info( 'Document uploaded: ' . $filename );

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
            Error_Logger::log( 'Document import blocked - limit reached' );
            return __( 'Upload limit reached.', 'council-debt-counters' );
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
        $filename = self::unique_filename( $filename );
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

    private static function maybe_extract_figures( string $file, int $council_id, string $financial_year ) {
        Error_Logger::log_info( 'AI extraction starting for council ' . $council_id . ' file ' . basename( $file ) );
        if ( ! file_exists( $file ) ) {
            Error_Logger::log_error( 'Document not found: ' . $file );
            return new \WP_Error( 'missing_file', __( 'Document not found.', 'council-debt-counters' ) );
        }
        if ( ! is_readable( $file ) ) {
            Error_Logger::log_error( 'Document not readable: ' . $file );
            return new \WP_Error( 'unreadable_file', __( 'Document not readable.', 'council-debt-counters' ) );
        }
        $size = filesize( $file );
        $ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        Error_Logger::log_debug( 'Reading document ' . $file . ' (' . $ext . ', ' . $size . ' bytes)' );
        $text = self::extract_text( $file );
        if ( empty( $text ) ) {
            $error = new \WP_Error( 'no_text', __( 'Failed to read document.', 'council-debt-counters' ) );
            Error_Logger::log_error( 'AI extraction error: ' . $error->get_error_message() . ' from ' . $file );
            return $error;
        }
        Error_Logger::log_debug( 'Extracted ' . strlen( $text ) . ' chars of text' );
        $data = AI_Extractor::extract_key_figures( $text );
        if ( is_wp_error( $data ) ) {
            Error_Logger::log_error( 'AI extraction error: ' . $data->get_error_message() );
            return $data;
        }
        if ( is_array( $data ) ) {
            self::store_ai_suggestions( $council_id, $financial_year, $data );
            $tokens = AI_Extractor::get_last_tokens();
            Error_Logger::log_info( 'AI extraction complete for council ' . $council_id . ' using ' . $tokens . ' tokens' );
            return [ 'data' => $data, 'tokens' => $tokens ];
        }
        $error = new \WP_Error( 'invalid_ai_data', __( 'Invalid AI response.', 'council-debt-counters' ) );
        Error_Logger::log_error( 'AI extraction error: unexpected data' );
        return $error;
    }

    private static function store_ai_suggestions( int $council_id, string $year, array $data ) {
        $all = get_option( 'cdc_ai_suggestions', [] );
        if ( ! isset( $all[ $council_id ] ) ) {
            $all[ $council_id ] = [];
        }
        $all[ $council_id ][ $year ] = $data;
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
        foreach ( $all as $cid => $years ) {
            $name = get_the_title( $cid );
            foreach ( $years as $year => $data ) {
                echo '<div class="notice notice-info"><p>';
                printf( esc_html__( 'OpenAI suggested figures for %s (%s). Review and confirm below.', 'council-debt-counters' ), esc_html( $name ), esc_html( $year ) );
                echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                echo '<input type="hidden" name="action" value="cdc_confirm_ai_figures" />';
                echo '<input type="hidden" name="council_id" value="' . esc_attr( $cid ) . '" />';
                echo '<input type="hidden" name="year" value="' . esc_attr( $year ) . '" />';
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
                echo '<input type="hidden" name="year" value="' . esc_attr( $year ) . '" />';
                wp_nonce_field( 'cdc_dismiss_ai_figures' );
                submit_button( __( 'Dismiss', 'council-debt-counters' ), 'secondary' );
                echo '</form></div>';
            }
        }
    }

    public static function handle_confirm_ai() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_confirm_ai_figures' );
        $cid   = intval( $_POST['council_id'] );
        $figures = (array) ( $_POST['figures'] ?? [] );
        $year  = sanitize_text_field( $_POST['year'] ?? CDC_Utils::current_financial_year() );
        foreach ( $figures as $field => $value ) {
            Custom_Fields::update_value( $cid, sanitize_key( $field ), sanitize_text_field( $value ), $year );
        }
        if ( method_exists( '\\CouncilDebtCounters\\Council_Post_Type', 'calculate_total_debt' ) ) {
            Council_Post_Type::calculate_total_debt( $cid, $year );
        }
        $all = get_option( 'cdc_ai_suggestions', [] );
        if ( isset( $all[ $cid ][ $year ] ) ) {
            unset( $all[ $cid ][ $year ] );
            if ( empty( $all[ $cid ] ) ) {
                unset( $all[ $cid ] );
            }
            update_option( 'cdc_ai_suggestions', $all );
        }
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_dismiss_ai() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_dismiss_ai_figures' );
        $cid  = intval( $_POST['council_id'] );
        $year = sanitize_text_field( $_POST['year'] ?? '' );
        $all  = get_option( 'cdc_ai_suggestions', [] );
        if ( $year && isset( $all[ $cid ][ $year ] ) ) {
            unset( $all[ $cid ][ $year ] );
            if ( empty( $all[ $cid ] ) ) {
                unset( $all[ $cid ] );
            }
            update_option( 'cdc_ai_suggestions', $all );
        }
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_ajax_extract_info() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ] );
        }

        $doc_id = intval( $_POST['doc_id'] ?? 0 );
        $doc    = $doc_id ? self::get_document_by_id( $doc_id ) : null;
        if ( ! $doc ) {
            wp_send_json_error( [ 'message' => __( 'Document not found.', 'council-debt-counters' ) ] );
        }

        $path = self::get_docs_path() . $doc->filename;
        $text = self::extract_text( $path );
        $tokens = $text ? ceil( strlen( $text ) / AI_Extractor::AVG_TOKEN_CHARS ) : 0;

        wp_send_json_success( [
            'year'   => $doc->financial_year,
            'tokens' => $tokens,
        ] );
    }

    public static function handle_ajax_extract() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ] );
        }


        if ( ! get_option( 'cdc_openai_api_key' ) ) {
            Error_Logger::log_error( 'AI extraction blocked: API key missing' );
            wp_send_json_error( [ 'message' => __( 'OpenAI API key not configured.', 'council-debt-counters' ) ] );
        }

        $doc_id = intval( $_POST['doc_id'] ?? 0 );
        Error_Logger::log_debug( 'AJAX extract requested for doc ' . $doc_id );
        $doc    = $doc_id ? self::get_document_by_id( $doc_id ) : null;
        if ( ! $doc ) {
            Error_Logger::log_error( 'AI extraction failed: document not found #' . $doc_id );
            wp_send_json_error( [ 'message' => __( 'Document not found.', 'council-debt-counters' ) ] );
        }

        if ( ! in_array( $doc->doc_type, self::DOC_TYPES, true ) || $doc->council_id <= 0 ) {
            Error_Logger::log_error( 'AI extraction failed: invalid document #' . $doc_id );
            wp_send_json_error( [ 'message' => __( 'Invalid document.', 'council-debt-counters' ) ] );
        }

        $path   = self::get_docs_path() . $doc->filename;
        Error_Logger::log_debug( 'Using file path ' . $path );
        $result = self::maybe_extract_figures( $path, (int) $doc->council_id, $doc->financial_year );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Extraction failed: %s', 'council-debt-counters' ), $result->get_error_message() ) ] );
        }

        $msg    = __( 'Extraction complete. Review suggestions below.', 'council-debt-counters' );
        $tokens = is_array( $result ) && isset( $result['tokens'] ) ? intval( $result['tokens'] ) : 0;
        wp_send_json_success( [ 'message' => $msg, 'tokens' => $tokens ] );
    }

    public static function handle_ajax_upload_doc() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ], 403 );
        }
        check_ajax_referer( 'cdc_save_council', 'nonce' );

        $cid  = intval( $_POST['council_id'] ?? 0 );
        if ( ! $cid ) {
            wp_send_json_error( [ 'message' => __( 'Invalid council.', 'council-debt-counters' ) ] );
        }
        $year     = sanitize_text_field( $_POST['year'] ?? self::current_financial_year() );
        $doc_type = sanitize_key( $_POST['doc_type'] ?? 'draft_statement_of_accounts' );
        if ( ! in_array( $doc_type, self::DOC_TYPES, true ) ) {
            $doc_type = 'draft_statement_of_accounts';
        }
        $filename = '';
        $result = null;
        if ( ! empty( $_FILES['file']['name'] ) ) {
            $filename = basename( $_FILES['file']['name'] );
            $result   = self::upload_document( $_FILES['file'], $doc_type, $cid, $year );
        } elseif ( ! empty( $_POST['url'] ) ) {
            $url      = esc_url_raw( $_POST['url'] );
            $filename = basename( parse_url( $url, PHP_URL_PATH ) );
            $result   = self::import_from_url( $url, $doc_type, $cid, $year );
        } elseif ( ! empty( $_POST['existing'] ) ) {
            $filename = sanitize_file_name( $_POST['existing'] );
            self::assign_document( $filename, $cid, $doc_type, $year );
            $result = true;
        } else {
            wp_send_json_error( [ 'message' => __( 'No document specified.', 'council-debt-counters' ) ] );
        }

        if ( $result === true ) {
            $doc = self::get_document( $filename );
            if ( $doc ) {
                wp_send_json_success( [
                    'html'    => self::render_doc_row( $doc ),
                    'message' => __( 'Document added.', 'council-debt-counters' ),
                ] );
            }
        }
        wp_send_json_error( [ 'message' => is_string( $result ) ? $result : __( 'Upload failed.', 'council-debt-counters' ) ] );
    }

    public static function render_doc_row( $doc ) {
        ob_start();
        ?>
        <tr>
            <td><?php echo esc_html( $doc->filename ); ?></td>
            <td>
                <select name="docs[<?php echo esc_attr( $doc->id ); ?>][financial_year]">
                    <?php foreach ( self::financial_years() as $y ) : ?>
                        <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $doc->financial_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="docs[<?php echo esc_attr( $doc->id ); ?>][doc_type]">
                    <?php foreach ( self::doc_type_labels() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $doc->doc_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <button type="button" value="<?php echo esc_attr( $doc->id ); ?>" class="button cdc-extract-ai"><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Extract Figures', 'council-debt-counters' ); ?></button>
                <button type="submit" name="update_doc" value="<?php echo esc_attr( $doc->id ); ?>" class="button button-secondary"><?php esc_html_e( 'Update', 'council-debt-counters' ); ?></button>
                <button type="submit" name="delete_doc" value="<?php echo esc_attr( $doc->id ); ?>" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this document?', 'council-debt-counters' ) ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></button>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    private static function extract_text( string $file ) {
        if ( ! file_exists( $file ) ) {
            Error_Logger::log_error( 'extract_text file missing: ' . $file );
            return '';
        }
        if ( ! is_readable( $file ) ) {
            Error_Logger::log_error( 'extract_text file unreadable: ' . $file );
            return '';
        }
        $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        Error_Logger::log_debug( 'extract_text extension ' . $ext );
        if ( $ext === 'pdf' ) {
            // Use the system pdftotext binary if available as it tends to be
            // more reliable with a wide range of PDF files.
            if ( function_exists( 'shell_exec' ) ) {
                $bin_check = shell_exec( 'command -v pdftotext' );
                if ( ! empty( $bin_check ) ) {
                    $cmd    = 'pdftotext ' . escapeshellarg( $file ) . ' -';
                    $output = shell_exec( $cmd );
                    if ( ! empty( $output ) ) {
                        Error_Logger::log_debug( 'pdftotext used for extraction' );
                        return $output;
                    }
                    Error_Logger::log_error( 'pdftotext returned no output for ' . $file );
                }
            }

            // Fallback to the PHP parser if pdftotext is unavailable or failed.
            if ( class_exists( '\\Smalot\\PdfParser\\Parser' ) ) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf    = $parser->parseFile( $file );
                    return $pdf->getText();
                } catch ( \Exception $e ) {
                    Error_Logger::log_error( 'PDF parse error: ' . $e->getMessage() );
                }
            }

            return '';
        }
        if ( $ext === 'csv' || $ext === 'txt' ) {
            $contents = file_get_contents( $file );
            if ( false === $contents ) {
                Error_Logger::log_error( 'Failed to read file: ' . $file );
                return '';
            }
            return $contents;
        }
        Error_Logger::log_debug( 'extract_text unsupported extension: ' . $ext );
        return '';
    }
}
