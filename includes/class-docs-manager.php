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

    public static function init() {
        add_action( 'init', [ __CLASS__, 'maybe_install' ] );
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
        }
    }

    public static function get_docs_path() {
        return plugin_dir_path( dirname( __FILE__ ) ) . self::DOCS_DIR . '/';
    }

    public static function list_documents( int $council_id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        if ( $council_id > 0 ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE council_id = %d", $council_id ) );
        }
        return $wpdb->get_results( "SELECT * FROM $table" );
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

    public static function upload_document( $file, string $doc_type = '', int $council_id = 0 ) {
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
            self::add_document( $filename, $doc_type, $council_id );
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
    public static function import_from_url( string $url, string $doc_type = '', int $council_id = 0 ) {
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
        self::add_document( $filename, $doc_type, $council_id );
        return true;
    }

    public static function get_document( string $filename ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE filename = %s", $filename ) );
    }

    public static function add_document( string $filename, string $doc_type = '', int $council_id = 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE, [
            'filename'   => $filename,
            'doc_type'   => $doc_type,
            'council_id' => $council_id,
        ], [ '%s', '%s', '%d' ] );
    }

    public static function assign_document( string $filename, int $council_id, string $doc_type ) {
        if ( ! in_array( $doc_type, self::DOC_TYPES, true ) ) {
            return false;
        }
        global $wpdb;
        $doc = self::get_document( $filename );
        if ( $doc ) {
            $wpdb->update( $wpdb->prefix . self::TABLE, [
                'council_id' => $council_id,
                'doc_type'   => $doc_type,
            ], [ 'id' => $doc->id ], [ '%d', '%s' ], [ '%d' ] );
        } else {
            self::add_document( $filename, $doc_type, $council_id );
        }
        return true;
    }
}
