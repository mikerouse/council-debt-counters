<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Whistleblower_Form {

    const CPT = 'waste_report';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'init', [ __CLASS__, 'maybe_handle_submission' ] );
        add_shortcode( 'report_waste_form', [ __CLASS__, 'render_form' ] );
    }

    public static function register_cpt() {
        register_post_type( self::CPT, [
            'labels' => [
                'name' => __( 'Waste Reports', 'council-debt-counters' ),
                'singular_name' => __( 'Waste Report', 'council-debt-counters' ),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => [ 'title', 'editor', 'custom-fields' ],
        ] );
    }

    public static function maybe_handle_submission() {
        if ( empty( $_POST['cdc_waste_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['cdc_waste_nonce'], 'cdc_waste' ) ) {
            return;
        }

        $desc  = sanitize_textarea_field( $_POST['cdc_description'] ?? '' );
        $email = sanitize_email( $_POST['cdc_email'] ?? '' );
        $attachment_id = 0;
        if ( ! empty( $_FILES['cdc_file']['name'] ) && ! empty( $_FILES['cdc_file']['tmp_name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded = wp_handle_upload( $_FILES['cdc_file'], [ 'test_form' => false ] );
            if ( ! empty( $uploaded['file'] ) ) {
                $attachment_id = wp_insert_attachment( [
                    'post_title' => basename( $uploaded['file'] ),
                    'post_type'  => 'attachment',
                    'post_mime_type' => $uploaded['type'],
                ], $uploaded['file'] );
            }
        }

        $post_id = wp_insert_post( [
            'post_type'   => self::CPT,
            'post_status' => 'private',
            'post_title'  => wp_trim_words( $desc, 6, '...' ),
            'post_content'=> $desc,
        ] );
        if ( $attachment_id ) {
            update_post_meta( $post_id, 'attachment_id', $attachment_id );
        }
        if ( $email ) {
            update_post_meta( $post_id, 'contact_email', $email );
        }
        $count = (int) get_option( 'cdc_waste_report_count', 0 );
        update_option( 'cdc_waste_report_count', $count + 1 );

        wp_safe_redirect( add_query_arg( 'report', 'thanks', wp_get_referer() ) );
        exit;
    }

    public static function render_form() {
        if ( isset( $_GET['report'] ) && 'thanks' === $_GET['report'] ) {
            return '<div class="alert alert-success">' . esc_html__( 'Thank you for your report.', 'council-debt-counters' ) . '</div>';
        }
        ob_start();
        ?>
        <form method="post" enctype="multipart/form-data" class="cdc-waste-form">
            <?php wp_nonce_field( 'cdc_waste', 'cdc_waste_nonce' ); ?>
            <div class="mb-3">
                <label for="cdc-description" class="form-label"><?php esc_html_e( 'Description of concern', 'council-debt-counters' ); ?></label>
                <textarea class="form-control" id="cdc-description" name="cdc_description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="cdc-file" class="form-label"><?php esc_html_e( 'Optional file', 'council-debt-counters' ); ?></label>
                <input type="file" class="form-control" id="cdc-file" name="cdc_file" />
            </div>
            <div class="mb-3">
                <label for="cdc-email" class="form-label"><?php esc_html_e( 'Contact email (optional)', 'council-debt-counters' ); ?></label>
                <input type="email" class="form-control" id="cdc-email" name="cdc_email" />
            </div>
            <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Submit Report', 'council-debt-counters' ); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }
}
