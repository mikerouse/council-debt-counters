<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Council_Admin_Page {
    const PAGE_SLUG = 'cdc-manage-councils';

    /**
     * Register hooks.
     */
    public static function init() {
        // Register submenu after the main plugin menu is added.
        add_action( 'admin_menu', [ __CLASS__, 'add_page' ], 11 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_post_cdc_save_council', [ __CLASS__, 'handle_save' ] );
    }

    public static function add_page() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Councils', 'council-debt-counters' ),
            __( 'Councils', 'council-debt-counters' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'debt-counters_page_' . self::PAGE_SLUG ) {
            return;
        }
        // Enqueue Bootstrap (CSS/JS)
        $plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
        $use_cdn     = apply_filters( 'cdc_use_cdn', false );

        if ( $use_cdn ) {
            $bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css';
            $bootstrap_js  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js';
        } else {
            $bootstrap_css = plugins_url( 'public/css/bootstrap.min.css', $plugin_file );
            $bootstrap_js  = plugins_url( 'public/js/bootstrap.bundle.min.js', $plugin_file );
        }

        wp_enqueue_style( 'bootstrap-5', $bootstrap_css, [], '5.3.1' );
        wp_enqueue_script( 'bootstrap-5', $bootstrap_js, [], '5.3.1', true );
        // Enqueue counter CSS/JS for real-time counter in admin
        wp_enqueue_style( 'cdc-counter', plugins_url( 'public/css/counter.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_enqueue_script( 'cdc-counter', plugins_url( 'public/js/counter.js', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0', true );
        wp_enqueue_script(
            'cdc-council-form',
            plugins_url( 'admin/js/council-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0',
            true
        );
        wp_enqueue_style( "cdc-ai-progress", plugins_url( "admin/css/ai-progress.css", dirname( __DIR__ ) . "/council-debt-counters.php" ), [], "0.1.0" );
        wp_localize_script( "cdc-council-form", "cdcAiMessages", [
            "start" => __( "Analysing document…", "council-debt-counters" ),
            "error" => __( "Extraction failed", "council-debt-counters" )
        ] );
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
        }
        check_admin_referer( 'cdc_save_council' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        $fields = Custom_Fields::get_fields();
        $title  = '';
        foreach ( $fields as $field ) {
            if ( $field->name === 'council_name' ) {
                $title = sanitize_text_field( $_POST['cdc_fields'][ $field->id ] ?? '' );
                break;
            }
        }

        if ( $post_id ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => $title ] );
        } else {
            $post_id = wp_insert_post( [ 'post_type' => 'council', 'post_status' => 'publish', 'post_title' => $title ] );
        }

        foreach ( $fields as $field ) {
            if ( $field->name === 'total_debt' || $field->name === 'statement_of_accounts' ) {
                continue;
            }
            $value = $_POST['cdc_fields'][ $field->id ] ?? '';
            Custom_Fields::update_value( $post_id, $field->name, wp_unslash( $value ) );
        }

        $soa_value = Custom_Fields::get_value( $post_id, 'statement_of_accounts' );
        $soa_year  = sanitize_text_field( $_POST['statement_of_accounts_year'] ?? Docs_Manager::current_financial_year() );

        if ( ! empty( $_FILES['statement_of_accounts_file']['name'] ) ) {
            $result = Docs_Manager::upload_document( $_FILES['statement_of_accounts_file'], 'statement_of_accounts', $post_id, $soa_year );
            if ( $result === true ) {
                $soa_value = sanitize_file_name( $_FILES['statement_of_accounts_file']['name'] );
            }
        } elseif ( ! empty( $_POST['statement_of_accounts_url'] ) ) {
            $url = esc_url_raw( $_POST['statement_of_accounts_url'] );
            $result = Docs_Manager::import_from_url( $url, 'statement_of_accounts', $post_id, $soa_year );
            if ( $result === true ) {
                $soa_value = sanitize_file_name( basename( parse_url( $url, PHP_URL_PATH ) ) );
            }
        } elseif ( ! empty( $_POST['statement_of_accounts_existing'] ) ) {
            $existing = sanitize_file_name( $_POST['statement_of_accounts_existing'] );
            Docs_Manager::assign_document( $existing, $post_id, 'statement_of_accounts', $soa_year );
            $soa_value = $existing;
        }

        if ( $soa_value ) {
            Custom_Fields::update_value( $post_id, 'statement_of_accounts', $soa_value );
        }

        // Document edits or deletions from the Documents tab
        if ( isset( $_POST['update_doc'] ) && isset( $_POST['docs'][ $_POST['update_doc'] ] ) ) {
            $doc_id = intval( $_POST['update_doc'] );
            $info   = $_POST['docs'][ $doc_id ];
            Docs_Manager::update_document( $doc_id, [
                'doc_type'      => sanitize_key( $info['doc_type'] ?? '' ),
                'financial_year'=> sanitize_text_field( $info['financial_year'] ?? '' ),
            ] );
        }

        if ( isset( $_POST['delete_doc'] ) ) {
            $doc_id = intval( $_POST['delete_doc'] );
            $doc    = Docs_Manager::get_document_by_id( $doc_id );
            if ( $doc ) {
                Docs_Manager::delete_document( $doc->filename );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        exit;
    }

    public static function render_page() {
        include plugin_dir_path( __DIR__ ) . 'admin/views/councils-page.php';
    }
}
