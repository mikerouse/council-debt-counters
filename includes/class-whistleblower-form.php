<?php
/**
 * Whistleblower form handling.
 *
 * @package CouncilDebtCounters
 */

namespace CouncilDebtCounters;

use CouncilDebtCounters\Error_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle whistleblower form submissions.
 */
class Whistleblower_Form {

	const CPT = 'waste_report';

		/**
		 * Register hooks and shortcodes.
		 */
        public static function init() {
                add_action( 'init', array( __CLASS__, 'register_cpt' ) );
                add_action( 'init', array( __CLASS__, 'maybe_handle_submission' ) );
                add_action( 'wp_ajax_cdc_report_waste', array( __CLASS__, 'ajax_submission' ) );
                add_action( 'wp_ajax_nopriv_cdc_report_waste', array( __CLASS__, 'ajax_submission' ) );
                add_shortcode( 'report_waste_form', array( __CLASS__, 'render_form' ) );
                add_shortcode( 'whistleblower_form', array( __CLASS__, 'render_form' ) );
        }

		/**
		 * Resolve a council ID from shortcode attributes.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return int Council post ID.
		 */
	private static function get_council_id_from_atts( array $atts ): int {
		$id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;
		if ( ! $id && ! empty( $atts['council'] ) ) {
			$post = get_page_by_title( sanitize_text_field( $atts['council'] ), OBJECT, 'council' );
			if ( $post ) {
				$id = $post->ID;
			}
		}
		return $id;
	}

		/**
		 * Register the custom post type used to store reports.
		 */
	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'   => array(
					'name'          => __( 'Waste Reports', 'council-debt-counters' ),
					'singular_name' => __( 'Waste Report', 'council-debt-counters' ),
				),
				'public'   => false,
				'show_ui'  => false,
				'supports' => array( 'title', 'editor', 'custom-fields' ),
			)
		);
	}

       /**
        * Process a waste report submission.
        *
        * @return int|\WP_Error Post ID on success or error object.
        */
       private static function process_submission() {
               if ( empty( $_POST['cdc_waste_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cdc_waste_nonce'] ) ), 'cdc_waste' ) ) {
                       return new \WP_Error( 'invalid', __( 'Security check failed.', 'council-debt-counters' ) );
               }

               $ip         = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
              $limit_key   = 'cdc_waste_limit_' . md5( $ip );
              $last_submit = get_transient( $limit_key );
              $cooldown    = MINUTE_IN_SECONDS * 5;
              Error_Logger::log_debug( 'Processing whistleblower submission from ' . $ip );
              if ( $last_submit && ( time() - (int) $last_submit ) < $cooldown ) {
                      Error_Logger::log_info( 'Rate limit triggered for IP ' . $ip );
                      return new \WP_Error( 'rate_limited', __( "Whoa there! You're blowing that whistle a bit too quickly for the system to believe you're a human. Grab a drink and take a few minutes to relax before trying again.", 'council-debt-counters' ) );
              }

               $council_id = isset( $_POST['cdc_council_id'] ) ? intval( wp_unslash( $_POST['cdc_council_id'] ) ) : 0;

               $site_key   = get_option( 'cdc_recaptcha_site_key', '' );
               $secret_key = get_option( 'cdc_recaptcha_secret_key', '' );
               if ( $site_key && $secret_key ) {
                       $token = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ?? '' ) );
                       if ( empty( $token ) ) {
                               Error_Logger::log_error( 'reCAPTCHA token missing for IP ' . $ip );
                               return new \WP_Error( 'recaptcha', __( 'reCAPTCHA verification failed.', 'council-debt-counters' ) );
                       }
                       Error_Logger::log_debug( 'Verifying reCAPTCHA for IP ' . $ip );
                       $verify = wp_remote_post(
                               'https://www.google.com/recaptcha/api/siteverify',
                               array(
                                       'body'    => array(
                                               'secret'   => $secret_key,
                                               'response' => $token,
                                               'remoteip' => $ip,
                                       ),
                                       'timeout' => 15,
                               )
                       );
                       $body = json_decode( wp_remote_retrieve_body( $verify ), true );
                       if ( empty( $body['success'] ) ) {
                               Error_Logger::log_error( 'reCAPTCHA failure for IP ' . $ip );
                               return new \WP_Error( 'recaptcha', __( 'reCAPTCHA verification failed.', 'council-debt-counters' ) );
                       }
               }

               $desc          = sanitize_textarea_field( wp_unslash( $_POST['cdc_description'] ?? '' ) );
               $email         = sanitize_email( wp_unslash( $_POST['cdc_email'] ?? '' ) );
               $attachment_id = 0;
               if ( ! empty( $_FILES['cdc_file']['name'] ) && ! empty( $_FILES['cdc_file']['tmp_name'] ) ) {
                       $max_size     = 5 * MB_IN_BYTES; // Limit file size to 5MB.
                       $allowed_mime = array(
                               'pdf'  => 'application/pdf',
                               'jpg'  => 'image/jpeg',
                               'jpeg' => 'image/jpeg',
                               'jpe'  => 'image/jpeg',
                               'png'  => 'image/png',
                               'gif'  => 'image/gif',
                       );

               if ( isset( $_FILES['cdc_file']['size'] ) && $_FILES['cdc_file']['size'] > $max_size ) {
                       Error_Logger::log_error( 'Uploaded file too large from IP ' . $ip );
                       return new \WP_Error( 'file_size', __( 'File exceeds maximum size of 5MB.', 'council-debt-counters' ) );
               }

                       require_once ABSPATH . 'wp-admin/includes/file.php';
                       $uploaded = wp_handle_upload(
                               $_FILES['cdc_file'],
                               array(
                                       'test_form' => false,
                                       'mimes'     => $allowed_mime,
                               )
                       );

                       if ( ! empty( $uploaded['file'] ) ) {
                               $filetype = wp_check_filetype( $uploaded['file'] );
                               if ( ! in_array( $filetype['type'], $allowed_mime, true ) ) {
                                       @unlink( $uploaded['file'] );
                                       Error_Logger::log_error( 'Invalid file type uploaded from IP ' . $ip );
                                       return new \WP_Error( 'file_type', __( 'Invalid file type.', 'council-debt-counters' ) );
                               }
                               $attachment_id = wp_insert_attachment(
                                       array(
                                               'post_title'     => basename( $uploaded['file'] ),
                                               'post_type'      => 'attachment',
                                               'post_mime_type' => $filetype['type'],
                                       ),
                                       $uploaded['file']
                               );
                               Error_Logger::log_debug( 'File uploaded for whistleblower report from ' . $ip );
                       }
               }

               $post_id = wp_insert_post(
                       array(
                               'post_type'    => self::CPT,
                               'post_status'  => 'private',
                               'post_title'   => wp_trim_words( $desc, 6, '...' ),
                               'post_content' => $desc,
                       )
               );
               if ( is_wp_error( $post_id ) ) {
                       return $post_id;
               }
               if ( $council_id ) {
                       update_post_meta( $post_id, 'council_id', $council_id );
               }
               if ( $attachment_id ) {
                       update_post_meta( $post_id, 'attachment_id', $attachment_id );
               }
               if ( $email ) {
                       update_post_meta( $post_id, 'contact_email', $email );
               }
               update_post_meta( $post_id, 'ip_address', $ip );
               $count = (int) get_option( 'cdc_waste_report_count', 0 );
               update_option( 'cdc_waste_report_count', $count + 1 );
               set_transient( $limit_key, time(), MINUTE_IN_SECONDS * 5 );
               Error_Logger::log_info( 'Whistleblower report saved with ID ' . $post_id . ' from ' . $ip );
               return $post_id;
       }

       /**
        * Handle standard (non-AJAX) submission.
        */
       public static function maybe_handle_submission() {
               if ( empty( $_POST['cdc_waste_nonce'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                       return;
               }

               if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                       return;
               }

               Error_Logger::log_debug( 'Handling standard whistleblower submission' );

               $result = self::process_submission();
               if ( is_wp_error( $result ) ) {
                       Error_Logger::log_error( 'Whistleblower submission error: ' . $result->get_error_message() );
                       wp_die( esc_html( $result->get_error_message() ) );
               }

               wp_safe_redirect( add_query_arg( 'report', 'thanks', wp_get_referer() ) );
               Error_Logger::log_info( 'Whistleblower report submitted via form with ID ' . $result );
               exit;
       }

       /**
        * Handle AJAX submissions.
        */
       public static function ajax_submission() {
               $result = self::process_submission();
               if ( is_wp_error( $result ) ) {
                       Error_Logger::log_error( 'Whistleblower AJAX error: ' . $result->get_error_message() );
                       wp_send_json_error( $result->get_error_message() );
               }

               Error_Logger::log_info( 'Whistleblower report submitted via AJAX with ID ' . $result );
               wp_send_json_success( __( 'Thank you for your report.', 'council-debt-counters' ) );
       }

		/**
		 * Render the whistleblower form for a specific council.
		 *
		 * @param array $atts Shortcode attributes. Must include 'id' or 'council'.
		 * @return string
		 */
	public static function render_form( $atts = array() ) {
		$council_id = self::get_council_id_from_atts( $atts );
		if ( ! $council_id ) {
			return '';
		}

		if ( isset( $_GET['report'] ) && 'thanks' === $_GET['report'] ) {
			return '<div class="alert alert-success">' . esc_html__( 'Thank you for your report.', 'council-debt-counters' ) . '</div>';
		}

               $site_key = get_option( 'cdc_recaptcha_site_key', '' );
               if ( $site_key ) {
                       wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/enterprise.js?render=' . $site_key, array(), null, true );
               }
               wp_enqueue_script( 'cdc-whistleblower-form', plugins_url( 'public/js/whistleblower-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ), array(), '0.1.0', true );
               wp_localize_script( 'cdc-whistleblower-form', 'cdcWhistle', array(
                       'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                       'siteKey'  => $site_key,
                       'success'  => __( 'Thank you for your report.', 'council-debt-counters' ),
                       'failure'  => __( 'Submission failed. Please try again.', 'council-debt-counters' ),
                       'delayMsg' => __( "Whoa there! You're blowing that whistle a bit too quickly for the system to believe you're a human. Grab a drink and take a few minutes to relax before trying again.", 'council-debt-counters' ),
                       'submitting' => __( 'Submitting', 'council-debt-counters' ),
               ) );

		ob_start();
		?>
               <form method="post" enctype="multipart/form-data" class="cdc-waste-form">
               <?php wp_nonce_field( 'cdc_waste', 'cdc_waste_nonce' ); ?>
                       <input type="hidden" name="cdc_council_id" value="<?php echo esc_attr( $council_id ); ?>">
                       <input type="hidden" name="cdc_ip" value="<?php echo esc_attr( $_SERVER['REMOTE_ADDR'] ?? '' ); ?>">
			<div class="mb-3">
				<label for="cdc-description" class="form-label"><?php esc_html_e( 'Description of concern', 'council-debt-counters' ); ?></label>
				<textarea class="form-control" id="cdc-description" name="cdc_description" required></textarea>
			</div>
			<div class="mb-3">
				<label for="cdc-file" class="form-label"><?php esc_html_e( 'Optional file (PDF or image, max 5MB)', 'council-debt-counters' ); ?></label>
				<input type="file" class="form-control" id="cdc-file" name="cdc_file" />
			</div>
			<div class="mb-3">
				<label for="cdc-email" class="form-label"><?php esc_html_e( 'Contact email (optional)', 'council-debt-counters' ); ?></label>
				<input type="email" class="form-control" id="cdc-email" name="cdc_email" />
			</div>
               <?php if ( $site_key ) : ?>
                               <input type="hidden" name="g-recaptcha-response" />
                       <?php endif; ?>
                       <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Submit Report', 'council-debt-counters' ); ?></button>
               </form>
               <div class="cdc-response mt-3"></div>
               <?php
               return ob_get_clean();
       }
}
