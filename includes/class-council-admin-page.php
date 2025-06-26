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
        add_action( 'wp_ajax_cdc_update_toolbar', [ __CLASS__, 'ajax_update_toolbar' ] );
        add_action( 'wp_ajax_cdc_get_year_values', [ __CLASS__, 'ajax_get_year_values' ] );
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
        $use_cdn     = apply_filters( 'cdc_use_cdn', (bool) get_option( 'cdc_use_cdn_assets', 0 ) );

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
        $font   = get_option( 'cdc_counter_font', 'Oswald' );
        $weight = get_option( 'cdc_counter_weight', '600' );
        $font_url = 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $font ) . ':wght@' . $weight . '&display=swap';
        wp_enqueue_style( 'cdc-counter-font', $font_url, [], null );
        wp_add_inline_style( 'cdc-counter-font', ".cdc-counter{font-family:'{$font}',sans-serif;font-weight:{$weight};}" );
        wp_enqueue_script( 'cdc-counter-animations' );
        wp_enqueue_media();
        wp_enqueue_script(
            'cdc-media-select',
            plugins_url( 'admin/js/media-select.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.0',
            true
        );
        wp_localize_script( 'cdc-media-select', 'CDC_MEDIA_SELECT', [
            'title'  => __( 'Select Image', 'council-debt-counters' ),
            'button' => __( 'Use this image', 'council-debt-counters' ),
        ] );
        wp_enqueue_script(
            'cdc-council-form',
            plugins_url( 'admin/js/council-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ),
            [],
            '0.1.4',
            true
        );
        wp_enqueue_style( 'cdc-ai-progress', plugins_url( 'admin/css/ai-progress.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_enqueue_style( 'cdc-year-progress', plugins_url( 'admin/css/year-progress.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_enqueue_style( 'cdc-upload-progress', plugins_url( 'admin/css/upload-progress.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.1' );
        wp_enqueue_style( 'cdc-toolbar', plugins_url( 'admin/css/toolbar.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_localize_script( 'cdc-council-form', 'cdcAiMessages', [
            'steps' => [
                __( 'Checking OpenAI API key…', 'council-debt-counters' ),
                __( 'Checking licence limits…', 'council-debt-counters' ),
                __( 'Connecting to OpenAI API…', 'council-debt-counters' ),
                __( 'Sending document for extraction…', 'council-debt-counters' ),
                __( 'Waiting for AI answers…', 'council-debt-counters' ),
            ],
            'error' => __( 'Extraction failed', 'council-debt-counters' ),
            'timeout' => apply_filters( 'cdc_openai_timeout', 60 ),
            'editPrompt' => __( 'Edit the question to send to AI', 'council-debt-counters' ),
            'ask'    => __( 'Ask AI', 'council-debt-counters' ),
            'cancel' => __( 'Cancel', 'council-debt-counters' ),
            'typeLabel' => __( 'Expected answer', 'council-debt-counters' ),
            'typeMoney' => __( 'Monetary figure', 'council-debt-counters' ),
            'typeInteger' => __( 'Integer number', 'council-debt-counters' ),
            'typeWord' => __( 'Single word', 'council-debt-counters' ),
            'typeSentence' => __( 'Short sentence', 'council-debt-counters' ),
            'responseLabel' => __( 'AI response', 'council-debt-counters' ),
            'accept' => __( 'Accept and Insert', 'council-debt-counters' ),
        ] );
        $council_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
        wp_localize_script( 'cdc-council-form', 'cdcToolbarData', [
            'id'    => $council_id,
            'nonce' => wp_create_nonce( 'cdc_save_council' ),
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

        $status = sanitize_key( $_POST['post_status'] ?? 'publish' );
        if ( ! in_array( $status, [ 'publish', 'draft', 'under_review' ], true ) ) {
            $status = 'publish';
        }

        if ( $post_id ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => $title, 'post_status' => $status ] );
        } else {
            $post_id = wp_insert_post( [ 'post_type' => 'council', 'post_status' => $status, 'post_title' => $title ] );
        }
        if ( 'under_review' === $status ) {
            update_post_meta( $post_id, 'cdc_under_review', '1' );
        } else {
            delete_post_meta( $post_id, 'cdc_under_review' );
        }

        $na_flags  = $_POST['cdc_na'] ?? array();
        $tab_years = $_POST['cdc_tab_year'] ?? array();
        foreach ( $fields as $field ) {
            if ( $field->name === 'total_debt' || $field->name === 'statement_of_accounts' ) {
                continue;
            }
            $value = $_POST['cdc_fields'][ $field->id ] ?? '';
            $tab   = Custom_Fields::get_field_tab( $field->name );
            $year  = isset( $tab_years[ $tab ] ) ? sanitize_text_field( $tab_years[ $tab ] ) : CDC_Utils::current_financial_year();
            Custom_Fields::update_value( $post_id, $field->name, wp_unslash( $value ), $year );
            $meta_key = 'cdc_na_' . $field->name;
            if ( isset( $na_flags[ $field->name ] ) ) {
                update_post_meta( $post_id, $meta_key, '1' );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
        // Ensure calculated field never marked as N/A
        delete_post_meta( $post_id, 'cdc_na_total_debt' );
        $debt_year = isset( $tab_years['debt'] ) ? sanitize_text_field( $tab_years['debt'] ) : CDC_Utils::current_financial_year();
        Council_Post_Type::calculate_total_debt( $post_id, $debt_year );

        $default_year  = get_post_meta( $post_id, 'cdc_default_financial_year', true );
        $current_total = (float) Custom_Fields::get_value( $post_id, 'total_debt', $default_year );
        if ( ! $default_year || $current_total <= 0 ) {
            update_post_meta( $post_id, 'cdc_default_financial_year', $debt_year );
        }

        $na_tabs = $_POST['cdc_na_tab'] ?? array();
        $enabled = (array) get_option( 'cdc_enabled_counters', array() );
        foreach ( $enabled as $tab_key ) {
            $meta_key = 'cdc_na_tab_' . $tab_key;
            if ( isset( $na_tabs[ $tab_key ] ) ) {
                update_post_meta( $post_id, $meta_key, '1' );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }

        $soa_value = Custom_Fields::get_value( $post_id, 'statement_of_accounts', CDC_Utils::current_financial_year() );
        $soa_year  = sanitize_text_field( $_POST['statement_of_accounts_year'] ?? Docs_Manager::current_financial_year() );
        $soa_type  = sanitize_key( $_POST['statement_of_accounts_type'] ?? 'draft_statement_of_accounts' );
        if ( ! in_array( $soa_type, Docs_Manager::DOC_TYPES, true ) ) {
            $soa_type = 'draft_statement_of_accounts';
        }

        if ( ! empty( $_FILES['statement_of_accounts_file']['name'] ) ) {
            $result = Docs_Manager::upload_document( $_FILES['statement_of_accounts_file'], $soa_type, $post_id, $soa_year );
            if ( $result === true ) {
                $soa_value = sanitize_file_name( $_FILES['statement_of_accounts_file']['name'] );
            }
        } elseif ( ! empty( $_POST['statement_of_accounts_url'] ) ) {
            $url = esc_url_raw( $_POST['statement_of_accounts_url'] );
            $result = Docs_Manager::import_from_url( $url, $soa_type, $post_id, $soa_year );
            if ( $result === true ) {
                $soa_value = sanitize_file_name( basename( parse_url( $url, PHP_URL_PATH ) ) );
            }
        } elseif ( ! empty( $_POST['statement_of_accounts_existing'] ) ) {
            $existing = sanitize_file_name( $_POST['statement_of_accounts_existing'] );
            Docs_Manager::assign_document( $existing, $post_id, $soa_type, $soa_year );
            $soa_value = $existing;
        }

        if ( $soa_value ) {
            Custom_Fields::update_value( $post_id, 'statement_of_accounts', $soa_value, CDC_Utils::current_financial_year() );
        }

        if ( isset( $_POST['assigned_user'] ) ) {
            update_post_meta( $post_id, 'assigned_user', intval( $_POST['assigned_user'] ) );
        }

        if ( isset( $_POST['cdc_sharing_image'] ) ) {
            $img = absint( $_POST['cdc_sharing_image'] );
            if ( $img ) {
                update_post_meta( $post_id, 'cdc_sharing_image', $img );
            } else {
                delete_post_meta( $post_id, 'cdc_sharing_image' );
            }
        }

        if ( isset( $_POST['cdc_confirm_takeover'], $_POST['cdc_parent_council'] ) ) {
            $parent = intval( $_POST['cdc_parent_council'] );
            if ( $parent ) {
                update_post_meta( $post_id, 'cdc_parent_council', $parent );
                $name = get_the_title( $parent );
                $link = get_permalink( $parent );
                $msg  = sprintf( __( 'This council is now part of <a href="%s">%s</a>', 'council-debt-counters' ), esc_url( $link ), $name );
                Custom_Fields::update_value( $post_id, 'status_message', $msg, CDC_Utils::current_financial_year() );
                Custom_Fields::update_value( $post_id, 'status_message_type', 'info', CDC_Utils::current_financial_year() );
            } else {
                delete_post_meta( $post_id, 'cdc_parent_council' );
            }
        }

        if ( isset( $_POST['cdc_no_accounts'] ) ) {
            update_post_meta( $post_id, 'cdc_no_accounts', '1' );
            Custom_Fields::update_value( $post_id, 'status_message_type', 'danger', CDC_Utils::current_financial_year() );
        } else {
            delete_post_meta( $post_id, 'cdc_no_accounts' );
        }

        if ( isset( $_POST['cdc_default_financial_year'] ) ) {
            update_post_meta( $post_id, 'cdc_default_financial_year', sanitize_text_field( $_POST['cdc_default_financial_year'] ) );
        }

        if ( isset( $_POST['cdc_enabled_years'] ) && is_array( $_POST['cdc_enabled_years'] ) ) {
            $years = array_map( 'sanitize_text_field', (array) $_POST['cdc_enabled_years'] );
            update_post_meta( $post_id, 'cdc_enabled_years', $years );
        } else {
            delete_post_meta( $post_id, 'cdc_enabled_years' );
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

        Error_Logger::log_info( 'Council saved: ' . $post_id );
        $redirect_year = rawurlencode( $debt_year );
        $redirect_tab  = isset( $_POST['active_tab'] ) ? sanitize_key( $_POST['active_tab'] ) : 'general';
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&post=' . $post_id . '&updated=1&year=' . $redirect_year . '&tab=' . rawurlencode( $redirect_tab ) ) );
        exit;
    }

    public static function ajax_update_toolbar() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
        }
        check_ajax_referer( 'cdc_save_council', 'nonce' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( __( 'Invalid council.', 'council-debt-counters' ) );
        }

        $message_parts = [];

        if ( isset( $_POST['assigned_user'] ) ) {
            update_post_meta( $post_id, 'assigned_user', intval( $_POST['assigned_user'] ) );
            $message_parts[] = __( 'Assignee updated.', 'council-debt-counters' );
        }

        if ( isset( $_POST['post_status'] ) ) {
            $status = sanitize_key( $_POST['post_status'] );
            if ( ! in_array( $status, [ 'publish', 'draft', 'under_review' ], true ) ) {
                $status = 'publish';
            }
            wp_update_post( [ 'ID' => $post_id, 'post_status' => $status ] );
            if ( 'under_review' === $status ) {
                update_post_meta( $post_id, 'cdc_under_review', '1' );
            } else {
                delete_post_meta( $post_id, 'cdc_under_review' );
            }
            $message_parts[] = __( 'Status updated.', 'council-debt-counters' );
        }

        Error_Logger::log_info( 'Toolbar updated for council ' . $post_id );

        wp_send_json_success( [ 'message' => implode( ' ', $message_parts ) ] );
    }

    /**
     * Fetch values for a specific financial year when the editor switches the
     * dropdown on a tab.
     */
    public static function ajax_get_year_values() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'council-debt-counters' ), 403 );
        }
        check_ajax_referer( 'cdc_save_council', 'nonce' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $tab     = sanitize_key( $_POST['tab'] ?? '' );
        $year    = sanitize_text_field( $_POST['year'] ?? CDC_Utils::current_financial_year() );
        if ( ! $post_id || ! $tab ) {
            wp_send_json_error( __( 'Invalid request.', 'council-debt-counters' ) );
        }

        $fields  = Custom_Fields::get_fields();
        $values  = [];
        $na_vals = [];
        foreach ( $fields as $field ) {
            if ( Custom_Fields::get_field_tab( $field->name ) !== $tab ) {
                continue;
            }
            $values[ $field->name ] = Custom_Fields::get_value( $post_id, $field->name, $year );
            $na_vals[ $field->name ] = get_post_meta( $post_id, 'cdc_na_' . $field->name, true );
        }

        wp_send_json_success( [ 'values' => $values, 'na' => $na_vals ] );
    }

    public static function render_page() {
        include dirname( __DIR__ ) . '/admin/views/councils-page.php';
    }
}
