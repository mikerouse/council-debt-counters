<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Error_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Figure_Submission_Form {
	const CPT = 'figure_submission';

	public static function init() {
			add_action( 'init', array( __CLASS__, 'register_cpt' ) );
			add_action( 'init', array( __CLASS__, 'maybe_handle_submission' ) );
			add_action( 'wp_ajax_cdc_submit_figure', array( __CLASS__, 'ajax_submission' ) );
			add_action( 'wp_ajax_nopriv_cdc_submit_figure', array( __CLASS__, 'ajax_submission' ) );
			add_shortcode( 'council_data_form', array( __CLASS__, 'render_form' ) );
	}

	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'   => array(
					'name'          => __( 'Figure Submissions', 'council-debt-counters' ),
					'singular_name' => __( 'Figure Submission', 'council-debt-counters' ),
				),
				'public'   => false,
				'show_ui'  => false,
				'supports' => array( 'title', 'editor', 'custom-fields' ),
			)
		);
	}

	private static function get_council_id_from_atts( array $atts ): int {
		$id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;
		if ( 0 === $id && isset( $atts['council_id'] ) ) {
			$id = intval( $atts['council_id'] );
		}
		if ( 0 === $id && ! empty( $atts['council'] ) ) {
			$post = get_page_by_title( sanitize_text_field( $atts['council'] ), OBJECT, 'council' );
			if ( ! $post ) {
				$post = get_page_by_path( sanitize_title( $atts['council'] ), OBJECT, 'council' );
			}
			if ( $post ) {
				$id = $post->ID;
			}
		}
		if ( 0 === $id && is_singular( 'council' ) ) {
			$id = get_the_ID();
		}
		return $id;
	}

	private static function process_submission() {
		if ( empty( $_POST['cdc_fig_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cdc_fig_nonce'] ) ), 'cdc_fig' ) ) {
			return new \WP_Error( 'invalid', __( 'Security check failed.', 'council-debt-counters' ) );
		}
		$ip        = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$limit_key = 'cdc_fig_limit_' . md5( $ip );
		$last      = get_transient( $limit_key );
		if ( $last && ( time() - (int) $last ) < 300 ) {
			return new \WP_Error( 'rate_limited', __( "Whoa there! You're submitting too quickly. Please wait before trying again.", 'council-debt-counters' ) );
		}
		$site_key   = get_option( 'cdc_recaptcha_site_key', '' );
		$secret_key = get_option( 'cdc_recaptcha_secret_key', '' );
		if ( $site_key && $secret_key ) {
			$token = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ?? '' ) );
			if ( empty( $token ) ) {
				return new \WP_Error( 'recaptcha', __( 'reCAPTCHA verification failed.', 'council-debt-counters' ) );
			}
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
			$body   = json_decode( wp_remote_retrieve_body( $verify ), true );
			if ( empty( $body['success'] ) ) {
				return new \WP_Error( 'recaptcha', __( 'reCAPTCHA verification failed.', 'council-debt-counters' ) );
			}
		}
		$cid                   = isset( $_POST['cdc_council_id'] ) ? intval( $_POST['cdc_council_id'] ) : 0;
		$note                  = sanitize_textarea_field( wp_unslash( $_POST['cdc_note'] ?? '' ) );
		$email                 = sanitize_email( wp_unslash( $_POST['cdc_email'] ?? '' ) );
				$figures       = $_POST['cdc_figures'] ?? array();
				$sources       = $_POST['cdc_sources'] ?? array();
				$clean         = array();
				$clean_sources = array();
		foreach ( $figures as $key => $val ) {
						$val = str_replace( array( ',', '£', '$' ), '', $val );
			if ( '' === $val ) {
										continue;
			}
						$clean[ sanitize_key( $key ) ] = floatval( $val );
			if ( isset( $sources[ $key ] ) ) {
						$clean_sources[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $sources[ $key ] ) );
			}
		}
		if ( empty( $clean ) || 0 === $cid ) {
						return new \WP_Error( 'invalid', __( 'Submission incomplete.', 'council-debt-counters' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'private',
				'post_title'   => get_the_title( $cid ),
				'post_content' => $note,
			)
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
				update_post_meta( $post_id, 'council_id', $cid );
				update_post_meta( $post_id, 'figures', $clean );
		if ( ! empty( $clean_sources ) ) {
				update_post_meta( $post_id, 'sources', $clean_sources );
		}
		if ( $email ) {
				update_post_meta( $post_id, 'contact_email', $email );
		}
		update_post_meta( $post_id, 'ip_address', $ip );
		set_transient( $limit_key, time(), 300 );

		$admins  = get_option( 'admin_email' );
		$subject = __( 'New figure submission', 'council-debt-counters' );
				/* translators: %s: Council title */
				$message = sprintf( __( 'New figures submitted for %s', 'council-debt-counters' ), get_the_title( $cid ) );
		wp_mail( $admins, $subject, $message );
		Error_Logger::log_info( 'Figure submission saved with ID ' . $post_id );
		return $post_id;
	}

	public static function maybe_handle_submission() {
		if ( empty( $_POST['cdc_fig_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
			$result = self::process_submission();
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
			wp_safe_redirect( add_query_arg( 'submitted', '1', wp_get_referer() ) );
			exit;
	}

		/**
		* Handle AJAX submissions.
		*/
	public static function ajax_submission() {
			$result = self::process_submission();
		if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
		}

			wp_send_json_success( __( 'Thank you for your submission. Your figures will be reviewed by a moderator before going live.', 'council-debt-counters' ) );
	}

	public static function render_form( $atts = array() ) {
		$council_id = self::get_council_id_from_atts( $atts );
		if ( isset( $_GET['submitted'] ) ) {
			return '<div class="alert alert-success">' . esc_html__( 'Thank you for your submission.', 'council-debt-counters' ) . '</div>';
		}
		$site_key = get_option( 'cdc_recaptcha_site_key', '' );
		if ( $site_key ) {
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/enterprise.js?render=' . $site_key, array(), '1.0', true );
		}
				wp_enqueue_style( 'bootstrap-5' );
				wp_enqueue_script( 'bootstrap-5' );
				wp_enqueue_script( 'cdc-figure-form', plugins_url( 'public/js/figure-form.js', dirname( __DIR__ ) . '/council-debt-counters.php' ), array(), '0.1.0', true );
				wp_localize_script(
					'cdc-figure-form',
					'cdcFig',
					array(
						'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
						'siteKey'    => $site_key,
						'success'    => __( 'Thank you for your submission. Your figures will be reviewed by a moderator before going live.', 'council-debt-counters' ),
						'failure'    => __( 'Submission failed. Please try again.', 'council-debt-counters' ),
						'submitting' => __( 'Submitting', 'council-debt-counters' ),
					)
				);
				ob_start();
				$fields = Custom_Fields::get_fields();
				$inputs = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f->type, array( 'number', 'money' ), true ) ) {
						$tab = Custom_Fields::get_field_tab( $f->name );
				if ( in_array( $tab, array( 'debt', 'spending', 'income', 'deficit', 'interest', 'reserves', 'consultancy' ), true ) ) {
					$inputs[] = $f;
				}
			}
		}
		?>
				<form method="post" class="cdc-fig-form">
						<?php wp_nonce_field( 'cdc_fig', 'cdc_fig_nonce' ); ?>
						<input type="hidden" name="cdc_council_id" value="<?php echo esc_attr( $council_id ); ?>" />
						<?php foreach ( $inputs as $field ) : ?>
								<div class="mb-3">
										<label for="fig-<?php echo esc_attr( $field->name ); ?>" class="form-label"><?php echo esc_html( $field->label ); ?></label>
										<div class="input-group">
												<span class="input-group-text">&pound;</span>
												<input type="text" inputmode="decimal" class="form-control" id="fig-<?php echo esc_attr( $field->name ); ?>" name="cdc_figures[<?php echo esc_attr( $field->name ); ?>]" />
										</div>
										<input type="text" class="form-control mt-1" id="src-<?php echo esc_attr( $field->name ); ?>" name="cdc_sources[<?php echo esc_attr( $field->name ); ?>]" placeholder="<?php esc_attr_e( 'Source for this figure', 'council-debt-counters' ); ?>" />
										<div class="form-text">
												<?php esc_html_e( 'Please enter whole numbers (e.g. 123,456,789.00) without the pound sign.', 'council-debt-counters' ); ?>
										</div>
								</div>
						<?php endforeach; ?>
						<div class="mb-3">
								<label for="cdc_note" class="form-label"><?php esc_html_e( 'Note (optional)', 'council-debt-counters' ); ?></label>
								<textarea class="form-control" id="cdc_note" name="cdc_note"></textarea>
						</div>
			<div class="mb-3">
				<label for="cdc_email" class="form-label"><?php esc_html_e( 'Email (optional)', 'council-debt-counters' ); ?></label>
				<input type="email" class="form-control" id="cdc_email" name="cdc_email" />
			</div>
						<?php if ( $site_key ) : ?>
								<input type="hidden" name="g-recaptcha-response" />
						<?php endif; ?>
						<button type="submit" class="btn btn-primary">
								<?php esc_html_e( 'Submit', 'council-debt-counters' ); ?>
						</button>
						<span class="spinner-border spinner-border-sm align-middle ms-2 d-none" role="status" aria-hidden="true"></span>
				</form>
				<div class="cdc-fig-response mt-3"></div>
				<p class="small text-muted mt-2">
						<?php esc_html_e( 'This site is protected by reCAPTCHA and the Google Privacy Policy and Terms of Service apply.', 'council-debt-counters' ); ?>
				</p>
				<p class="small text-muted">
						<?php
						printf(
								/* translators: %s: IP address */
							esc_html__( 'Your IP address %s will be recorded with this submission to prevent abuse.', 'council-debt-counters' ),
							esc_html( $_SERVER['REMOTE_ADDR'] ?? '' )
						);
						?>
				</p>
				<?php
				return ob_get_clean();
	}
}
