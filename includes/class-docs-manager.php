<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Docs_Manager {
    const DOCS_DIR = 'docs';
    const ALLOWED_EXTENSIONS = ['csv', 'pdf', 'xlsx'];
    const FREE_LIMIT = 10;

    public static function get_docs_path() {
        return plugin_dir_path( dirname( __FILE__ ) ) . self::DOCS_DIR . '/';
    }

    public static function list_documents() {
        $files = [];
        $dir = self::get_docs_path();
        if ( is_dir( $dir ) ) {
            foreach ( scandir( $dir ) as $file ) {
                if ( in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), self::ALLOWED_EXTENSIONS ) ) {
                    $files[] = $file;
                }
            }
        }
        return $files;
    }

    public static function can_upload() {
        $is_pro = License_Manager::is_valid();
        if ( $is_pro ) return true;
        return count( self::list_documents() ) < self::FREE_LIMIT;
    }

    public static function upload_document( $file ) {
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS ) ) {
            return __( 'Invalid file type. Only XLSX, CSV, and PDF are allowed.', 'council-debt-counters' );
        }
        if ( ! self::can_upload() ) {
            return __( 'Free version limit reached. Upgrade to Pro for unlimited documents.', 'council-debt-counters' );
        }
        $target = self::get_docs_path() . basename( $file['name'] );
        if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
            return true;
        }
        return __( 'Upload failed.', 'council-debt-counters' );
    }

    public static function delete_document( $filename ) {
        $file = self::get_docs_path() . $filename;
        if ( file_exists( $file ) ) {
            unlink( $file );
            return true;
        }
        return false;
    }
}
