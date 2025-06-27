<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Custom_Fields;
use CouncilDebtCounters\CDC_Utils;
use CouncilDebtCounters\Figure_Submission_Form;

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

class Shortcode_Renderer {

	private static function default_labels(): array {
			return array(
				'debt'        => __( 'Debt', 'council-debt-counters' ),
				'spending'    => __( 'Spending', 'council-debt-counters' ),
				'income'      => __( 'Income', 'council-debt-counters' ),
				'deficit'     => __( 'Deficit', 'council-debt-counters' ),
				'interest'    => __( 'Interest', 'council-debt-counters' ),
				'reserves'    => __( 'Reserves', 'council-debt-counters' ),
				'consultancy' => __( 'Consultancy', 'council-debt-counters' ),
			);
	}

	private static function counter_title( string $type ): string {
			$defaults = self::default_labels();
			$titles   = (array) get_option( 'cdc_counter_titles', array() );
			$default  = $defaults[ $type ] ?? ucwords( $type );
			return sanitize_text_field( $titles[ $type ] ?? $default );
	}

	private static function total_default_labels(): array {
			return array(
				'debt'        => __( 'Total Debt', 'council-debt-counters' ),
				'spending'    => __( 'Total Spending', 'council-debt-counters' ),
				'income'      => __( 'Total Income', 'council-debt-counters' ),
				'deficit'     => __( 'Total Deficit', 'council-debt-counters' ),
				'interest'    => __( 'Total Interest', 'council-debt-counters' ),
				'reserves'    => __( 'Total Reserves', 'council-debt-counters' ),
				'consultancy' => __( 'Consultancy Spend', 'council-debt-counters' ),
			);
	}

	private static function total_counter_title( string $type ): string {
			$defaults = self::total_default_labels();
			$titles   = (array) get_option( 'cdc_total_counter_titles', array() );
			$default  = $defaults[ $type ] ?? ucwords( $type );
			return sanitize_text_field( $titles[ $type ] ?? $default );
	}

	private static function total_counter_year( string $type ): string {
			$years = (array) get_option( 'cdc_total_counter_years', array() );
			$year  = $years[ $type ] ?? get_option( 'cdc_default_financial_year', '2023/24' );
		if ( ! preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
				$year = '2023/24';
		}
			return $year;
	}

		/**
		* Get the URL for an icon asset.
		*
		* @param string $name Icon filename without extension.
		* @return string Icon URL.
		*/
	private static function icon_url( string $name ): string {
			$plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
			return plugins_url( 'public/icons/' . $name . '.svg', $plugin_file );
	}


		/**
		 * Render an annual counter for a specific type of financial data relating to a council.
		 * @param int $id
		 * @param string $field
		 * @param string $type
		 * @param bool $with_details
		 * @return bool|string
		 */
	private static function render_annual_counter( int $id, string $field, string $type = '', bool $with_details = true ) {
			// Check if the tab is enabled and don't show the counter if it is not
			$enabled = (array) get_option( 'cdc_enabled_counters', array() );
		if ( '' !== $type && ! in_array( $type, $enabled, true ) ) {
				return '';
		}
			// Check if the council is under review and don't show the counter if it is
		if ( CDC_Utils::is_under_review( $id ) ) {
				return '';
		}
			// Work with the most recent enabled year for this council.
			$year      = CDC_Utils::latest_enabled_year( $id );
			$raw_value = Custom_Fields::get_value( $id, $field, $year );
			// Get a parent council ID if this is a child council (if the council is a child it means the council has been taken over and no longer exists)
			$parent = intval( get_post_meta( $id, 'cdc_parent_council', true ) );
			// If the tab is set to 'Do not show this counter' we don't need to show this counter
			$dont_show_this_counter = $type ? get_post_meta( $id, 'cdc_na_tab_' . $type, true ) : '';
		if ( $dont_show_this_counter ) {
				return '';
		}
			// If the field is not set, check if it is marked as not applicable (this is for cases where we can't find the figure or the council doesn't report it but it's still a valid field)
			$na_field = get_post_meta( $id, 'cdc_na_' . $field, true );
		if ( $na_field ) {
				// If the field is marked as not applicable, we show a warning message
				$obj   = Custom_Fields::get_field_by_name( $field );
				$label = $obj && ! empty( $obj->label ) ? $obj->label : ucwords( str_replace( '_', ' ', $field ) );
				$map   = array(
					'debt'        => __( 'Debt figures not available', 'council-debt-counters' ),
					'spending'    => __( 'Expenditure figures not available', 'council-debt-counters' ),
					'income'      => __( 'Income figures not available', 'council-debt-counters' ),
					'deficit'     => __( 'Reported deficit figures not available', 'council-debt-counters' ),
					'interest'    => __( 'Interest payments not available', 'council-debt-counters' ),
					'reserves'    => __( 'Reserves figures not available', 'council-debt-counters' ),
					'consultancy' => __( 'Consultancy spend figures not available', 'council-debt-counters' ),
				);
				$msg   = $map[ $type ] ?? sprintf( __( '%s not available', 'council-debt-counters' ), $label );
				return '<div class="alert alert-warning m-1">' . esc_html( $msg ) . '</div>';
		}
			// If the raw value is empty or null, we show a warning message
		if ( '' === $raw_value || null === $raw_value ) {
				// If there is no figure because the council has been taken over
			if ( $parent ) {
					return '<div class="alert alert-info">' . esc_html__( 'No Longer Exists', 'council-debt-counters' ) . '</div>';
			}
				$label = $field;
				$obj   = Custom_Fields::get_field_by_name( $field );
			if ( $obj && ! empty( $obj->label ) ) {
					$label = $obj->label;
			} else {
					$label = ucwords( str_replace( '_', ' ', $label ) );
			}
				return sprintf(
					'<div class="alert alert-danger">%s</div>',
					esc_html(
						sprintf(
								/* translators: %s: Field label */
							__( 'No %s figure found', 'council-debt-counters' ),
							$label
						)
					)
				);
		}

			// If the raw value resolves to zero, attempt deeper inspection to locate the figure
		if ( 0.0 === (float) $raw_value ) {
					list( $replacement, $details ) = self::gather_zero_value_debug_info( $id, $field, $year );
					Error_Logger::log_debug( $details );
					wp_mail( get_option( 'admin_email' ), __( 'CDC zero value troubleshooting', 'council-debt-counters' ), $details );

			if ( '' !== $replacement ) {
						$raw_value = $replacement;
			} else {
					$label = $field;
					return sprintf(
						'<div class="alert alert-danger">%s</div>',
						esc_html(
							sprintf(
									/* translators: %s: Field label */
								__( 'No %s figure found', 'council-debt-counters' ),
								$label
							)
						)
					);
			}
		}

				// If we do have a figure, but the council has been taken over, we show the last figure as a static value (such as the outgoing council's debt)
		if ( $parent ) {
				return '<div class="cdc-counter-static fw-bold">£' . esc_html( number_format_i18n( (float) $raw_value, 2 ) ) . '</div>';
		}

				// The annual figure represents the total for the selected financial year.
				$annual = (float) $raw_value;

				// Enqueue the necessary styles and scripts
				wp_enqueue_style( 'bootstrap-5' );
				wp_enqueue_style( 'cdc-counter' );
				wp_enqueue_style( 'cdc-counter-font' );
				wp_enqueue_script( 'font-awesome-kit' );
				wp_enqueue_script( 'bootstrap-5' );
				wp_enqueue_script( 'cdc-counter-animations' );

				// Prepare the counter ID, class, label, and title (e.g. "Debt", "Spending", etc.)
				$counter_id    = 'cdc-counter-' . $id . '-' . sanitize_html_class( $field );
				$counter_class = 'cdc-counter-' . sanitize_html_class( $field );
				$obj           = Custom_Fields::get_field_by_name( $field );
				$label         = $obj && ! empty( $obj->label ) ? $obj->label : ucwords( str_replace( '_', ' ', $field ) );
				$title         = self::counter_title( $type ?: $field );
				$collapse_id   = 'cdc-detail-' . $id . '-' . sanitize_html_class( $field );
				$info_line     = self::counter_info( $id, $type ?: $field, $year );
				// Prepare the HTML output for the counter
				ob_start();
		?>
				<div class="cdc-counter-title text-center">
					<?php echo esc_html( $title ); ?>
					<?php if ( $with_details ) : ?>
								<button class="btn btn-link p-0 ms-1 cdc-info-btn" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
										<i class="fas fa-info-circle" aria-hidden="true"></i><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
								</button>
						<?php endif; ?>
				</div>
				<div class="cdc-counter-wrapper text-center mb-3">
								<?php $duration = max( 1, (int) get_option( 'cdc_counter_duration', 15 ) ); ?>
								<div id="<?php echo esc_attr( $counter_id ); ?>" class="cdc-counter <?php echo esc_attr( $counter_class ); ?> display-6 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $annual ); ?>" data-growth="0" data-start="0" data-duration="<?php echo esc_attr( $duration ); ?>" data-prefix="£" data-cid="<?php echo esc_attr( $id ); ?>" data-field="<?php echo esc_attr( $field ); ?>" data-year="<?php echo esc_attr( $year ); ?>">
								&hellip;
						</div>
					<?php if ( $info_line ) : ?>
								<div class="cdc-counter-info small text-muted" data-items="<?php echo esc_attr( wp_json_encode( array( $info_line ) ) ); ?>"></div>
						<?php endif; ?>
				</div>
				<?php if ( $with_details ) : ?>
						<div class="collapse" id="<?php echo esc_attr( $collapse_id ); ?>">
<div class="text-center cdc-counter-details">
<p class="mt-2 text-muted">
					<?php echo esc_html( self::counter_description_text( $type ?: $field ) ); ?>
</p>
</div>
						</div>
				<?php endif; ?>
				<?php
				return ob_get_clean();
	}

	public static function init() {
			add_shortcode( 'council_counter', array( __CLASS__, 'render_debt_counter' ) );
			add_shortcode( 'council_counters', array( __CLASS__, 'render_council_counters' ) );
			add_shortcode( 'spending_counter', array( __CLASS__, 'render_spending_counter' ) );
			add_shortcode( 'deficit_counter', array( __CLASS__, 'render_deficit_counter' ) );
			add_shortcode( 'interest_counter', array( __CLASS__, 'render_interest_counter' ) );
			add_shortcode( 'revenue_counter', array( __CLASS__, 'render_revenue_counter' ) );
			add_shortcode( 'custom_counter', array( __CLASS__, 'render_custom_counter' ) );
			add_shortcode( 'total_debt_counter', array( __CLASS__, 'render_total_debt_counter' ) );
			add_shortcode( 'total_spending_counter', array( __CLASS__, 'render_total_spending_counter' ) );
			add_shortcode( 'total_deficit_counter', array( __CLASS__, 'render_total_deficit_counter' ) );
			add_shortcode( 'total_interest_counter', array( __CLASS__, 'render_total_interest_counter' ) );
			add_shortcode( 'total_revenue_counter', array( __CLASS__, 'render_total_revenue_counter' ) );
			add_shortcode( 'total_custom_counter', array( __CLASS__, 'render_total_custom_counter' ) );
			add_shortcode( 'cdc_leaderboard', array( __CLASS__, 'render_leaderboard' ) );
			add_shortcode( 'cdc_share_buttons', array( __CLASS__, 'render_share_buttons' ) );
			add_shortcode( 'council_status', array( __CLASS__, 'render_status_message' ) );
			add_shortcode( 'missing_data_prompt', array( __CLASS__, 'render_missing_prompt' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
			add_action( 'wp_ajax_cdc_log_js', array( __CLASS__, 'ajax_log_js' ) );
			add_action( 'wp_ajax_nopriv_cdc_log_js', array( __CLASS__, 'ajax_log_js' ) );
			add_action( 'wp_ajax_cdc_log_share', array( __CLASS__, 'ajax_log_share' ) );
			add_action( 'wp_ajax_nopriv_cdc_log_share', array( __CLASS__, 'ajax_log_share' ) );
			add_action( 'wp_ajax_cdc_render_counters', array( __CLASS__, 'ajax_render_counters' ) );
			add_action( 'wp_ajax_nopriv_cdc_render_counters', array( __CLASS__, 'ajax_render_counters' ) );
			add_action( 'wp_ajax_cdc_render_leaderboard', array( __CLASS__, 'ajax_render_leaderboard' ) );
			add_action( 'wp_ajax_nopriv_cdc_render_leaderboard', array( __CLASS__, 'ajax_render_leaderboard' ) );
			add_action( 'wp_ajax_cdc_get_counter_value', array( __CLASS__, 'ajax_get_counter_value' ) );
			add_action( 'wp_ajax_nopriv_cdc_get_counter_value', array( __CLASS__, 'ajax_get_counter_value' ) );
	}

	public static function register_assets() {
			$plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
			$use_cdn     = apply_filters( 'cdc_use_cdn', (bool) get_option( 'cdc_use_cdn_assets', 0 ) );

		if ( $use_cdn ) {
				$bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css';
				$bootstrap_js  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js';
				$countup_js    = 'https://cdn.jsdelivr.net/npm/countup.js@2.6.2/dist/countUp.umd.js';
		} else {
				$bootstrap_css = plugins_url( 'public/css/bootstrap.min.css', $plugin_file );
				$bootstrap_js  = plugins_url( 'public/js/bootstrap.bundle.min.js', $plugin_file );
				$countup_js    = plugins_url( 'public/js/countUp.umd.js', $plugin_file );
		}

			$fa_script = 'https://kit.fontawesome.com/3a98f39235.js';

			wp_register_style( 'cdc-counter', plugins_url( 'public/css/counter.css', $plugin_file ), array(), '0.1.0' );
			$font     = get_option( 'cdc_counter_font', 'Oswald' );
			$weight   = get_option( 'cdc_counter_weight', '600' );
			$font_url = 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $font ) . ':wght@' . $weight . '&display=swap';
			wp_register_style( 'cdc-counter-font', $font_url, array(), null );
			wp_add_inline_style( 'cdc-counter-font', ".cdc-counter, .cdc-counter-static{font-family:'{$font}',sans-serif;font-weight:{$weight};}" );
			wp_register_script( 'countup', $countup_js, array(), '2.6.2', true );
			wp_register_script( 'cdc-counter-animations', plugins_url( 'public/js/counter-animations.js', $plugin_file ), array( 'countup' ), '0.1.0', true );
			wp_register_script( 'cdc-share-tracking', plugins_url( 'public/js/share-tracking.js', $plugin_file ), array(), '0.1.0', true );
			wp_register_style( 'bootstrap-5', $bootstrap_css, array(), '5.3.1' );
			wp_register_script( 'bootstrap-5', $bootstrap_js, array(), '5.3.1', true );
			wp_register_script( 'font-awesome-kit', $fa_script, array(), null, false );
			wp_register_script( 'cdc-council-counters', plugins_url( 'public/js/council-counters.js', $plugin_file ), array( 'bootstrap-5' ), '0.1.0', true );
			wp_localize_script( 'cdc-council-counters', 'cdcCounters', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script( 'cdc-counter-animations', 'cdcCounters', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
			wp_register_script( 'cdc-fig-modal', plugins_url( 'public/js/figure-form-modal.js', $plugin_file ), array( 'bootstrap-5' ), '0.1.0', true );
			wp_register_script( 'cdc-leaderboard', plugins_url( 'public/js/leaderboard.js', $plugin_file ), array( 'bootstrap-5' ), '0.1.0', true );
			wp_localize_script( 'cdc-leaderboard', 'cdcLeaderboard', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
			wp_localize_script(
				'cdc-counter-animations',
				'CDC_LOGGER',
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'cdc_log_js' ),
					'logLevel' => get_option( 'cdc_log_level', 'standard' ),
				)
			);
	}

		/**
		*  Render a shortcode for displaying a council counter.
		* @param mixed $atts
		* @return bool|string
		*/
	public static function render_debt_counter( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
			return self::render_annual_counter( $id, 'total_debt', 'debt' );
	}

	public static function render_spending_counter( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
			return self::render_annual_counter( $id, 'annual_spending', 'spending' );
	}

	public static function render_deficit_counter( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
			return self::render_annual_counter( $id, 'annual_deficit', 'deficit' );
	}

	public static function render_interest_counter( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
			return self::render_annual_counter( $id, 'interest_paid', 'interest' );
	}

	public static function render_revenue_counter( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
			return self::render_annual_counter( $id, 'total_income', 'income' );
	}

	public static function render_custom_counter( $atts ) {
			$id   = CDC_Utils::resolve_council_id( $atts );
			$type = sanitize_key( $atts['type'] ?? '' );
		if ( 0 === $id || '' === $type ) {
				return '';
		}
			$map = array(
				'reserves'    => 'usable_reserves',
				'spending'    => 'annual_spending',
				'income'      => 'total_income',
				'deficit'     => 'annual_deficit',
				'interest'    => 'interest_paid',
				'consultancy' => 'consultancy_spend',
			);
			if ( ! isset( $map[ $type ] ) ) {
					return '';
			}
			return self::render_annual_counter( $id, $map[ $type ], $type );
	}

	public static function render_share_buttons( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}

			$name      = get_the_title( $id );
			$year      = CDC_Utils::latest_enabled_year( $id );
			$interest  = (float) Custom_Fields::get_value( $id, 'interest_paid', $year );
			$debt      = (float) Custom_Fields::get_value( $id, 'total_debt', $year );
			$permalink = get_permalink( $id );

		if ( $interest > 0 ) {
				$message = sprintf( __( '%1$s spends £%2$s a year on debt interest. Find out more:', 'council-debt-counters' ), $name, number_format_i18n( $interest, 1 ) );
		} else {
				$message = sprintf( __( '%1$s’s debt is £%2$s. See how it compares:', 'council-debt-counters' ), $name, number_format_i18n( $debt, 0 ) );
		}

			$encoded = rawurlencode( $message . ' ' . $permalink );

			wp_enqueue_style( 'bootstrap-5' );
			wp_enqueue_script( 'bootstrap-5' );
			wp_enqueue_script( 'cdc-share-tracking' );
			wp_localize_script(
				'cdc-share-tracking',
				'cdcShare',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cdc_log_share' ),
				)
			);

			ob_start();
		?>
				<div class="cdc-share-buttons mt-3">
						<div class="fw-bold mb-1"><?php esc_html_e( 'Share this', 'council-debt-counters' ); ?></div>
						<a class="btn btn-outline-primary btn-sm me-2 d-inline-flex align-items-center cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="twitter" target="_blank" rel="noopener noreferrer" href="https://x.com/intent/tweet?text=<?php echo esc_attr( $encoded ); ?>">
								<img src="<?php echo esc_url( self::icon_url( 'twitter-x' ) ); ?>" alt="" width="16" height="16" class="me-1">
								<span><?php esc_html_e( 'X', 'council-debt-counters' ); ?></span>
						</a>
						<a class="btn btn-outline-success btn-sm me-2 d-inline-flex align-items-center cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="whatsapp" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?php echo esc_attr( $encoded ); ?>">
								<img src="<?php echo esc_url( self::icon_url( 'whatsapp' ) ); ?>" alt="" width="16" height="16" class="me-1">
								<span><?php esc_html_e( 'WhatsApp', 'council-debt-counters' ); ?></span>
						</a>
						<a class="btn btn-outline-primary btn-sm d-inline-flex align-items-center cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="facebook" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( rawurlencode( $permalink ) ); ?>">
								<img src="<?php echo esc_url( self::icon_url( 'facebook' ) ); ?>" alt="" width="16" height="16" class="me-1">
								<span><?php esc_html_e( 'Facebook', 'council-debt-counters' ); ?></span>
						</a>
				</div>
				<?php
				return ob_get_clean();
	}

	public static function render_status_message( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}

			$year    = CDC_Utils::latest_enabled_year( $id );
			$message = Custom_Fields::get_value( $id, 'status_message', $year );
			$type    = Custom_Fields::get_value( $id, 'status_message_type', $year );

		if ( ! is_string( $message ) || '' === trim( $message ) ) {
				return '';
		}

			$type = in_array( $type, array( 'info', 'warning', 'danger' ), true ) ? $type : 'info';

			return sprintf( '<div class="alert alert-%1$s" role="status">%2$s</div>', esc_attr( $type ), wp_kses_post( $message ) );
	}

	public static function render_missing_prompt( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}

		if ( ! CDC_Utils::is_under_review( $id ) ) {
					return '';
		}

			wp_enqueue_style( 'bootstrap-5' );
			wp_enqueue_script( 'bootstrap-5' );
			wp_enqueue_script( 'cdc-figure-form' );
			wp_enqueue_script( 'cdc-fig-modal' );

			$form = Figure_Submission_Form::render_form(
				array(
					'id'           => $id,
					'no_sources'   => true,
					'auto_approve' => true,
				)
			);

			ob_start();
		?>
				<div class="alert alert-info">
					<?php esc_html_e( 'This council is awaiting review. Help us build the UK\'s only public database of key financial figures for local government.', 'council-debt-counters' ); ?>
						<a href="#" class="cdc-open-fig-modal ms-1"><?php esc_html_e( 'Click or tap here to submit the figures for this council', 'council-debt-counters' ); ?></a>
				</div>
				<div class="modal fade" id="cdc-fig-modal" tabindex="-1" aria-hidden="true">
						<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
										<div class="modal-header">
												<h5 class="modal-title"><?php esc_html_e( 'Submit Figures', 'council-debt-counters' ); ?></h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">
											<?php echo $form; ?>
										</div>
								</div>
						</div>
				</div>
				<?php
				return ob_get_clean();
	}

		/**
		* Gather troubleshooting information when a zero value is detected.
		* Returns a replacement value if one can be derived along with log info.
		*
		* @param int    $id    Council post ID.
		* @param string $field Field name.
		* @param string $year  Financial year.
		* @return array{string,string} [replacement value, log details]
		*/
	private static function gather_zero_value_debug_info( int $id, string $field, string $year ): array {
			global $wpdb;
			$lines         = array();
			$lines[]       = "Zero value detected for field {$field} on council ID {$id} for {$year}.";
			$fields_table  = $wpdb->prefix . Custom_Fields::TABLE_FIELDS;
			$values_table  = $wpdb->prefix . Custom_Fields::TABLE_VALUES;
			$field_id      = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $fields_table WHERE name = %s", $field ) );
			$db_value      = '';
			$backend_value = null;
			$replacement   = '';

		if ( $field_id ) {
				$sql      = $wpdb->prepare( "SELECT value FROM $values_table WHERE council_id = %d AND field_id = %d AND financial_year = %s", $id, $field_id, $year );
				$lines[]  = 'Custom field SQL: ' . $sql;
				$db_value = $wpdb->get_var( $sql );
				$lines[]  = 'Result: ' . var_export( $db_value, true );

				$hist_sql = $wpdb->prepare( "SELECT financial_year, value FROM $values_table WHERE council_id = %d AND field_id = %d ORDER BY financial_year DESC", $id, $field_id );
				$lines[]  = 'Custom field history SQL: ' . $hist_sql;
			foreach ( $wpdb->get_results( $hist_sql ) as $row ) {
					$lines[] = '- ' . $row->financial_year . ': ' . var_export( $row->value, true );
			}
		}

			$meta_sql = $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s", $id, $wpdb->esc_like( $field ) . '%' );
			$lines[]  = 'Postmeta SQL: ' . $meta_sql;
		foreach ( $wpdb->get_results( $meta_sql ) as $row ) {
					$lines[] = '- ' . $row->meta_key . ': ' . var_export( $row->meta_value, true );
			if ( $row->meta_key === $field . '_' . $year || $row->meta_key === $field ) {
				$backend_value = $row->meta_value;
			}
		}

			$calc_total = null;
		if ( 'total_debt' === $field ) {
					$components    = array(
						'current_liabilities',
						'long_term_liabilities',
						'finance_lease_pfi_liabilities',
						'manual_debt_entry',
					);
					$component_sum = 0.0;
					$lines[]       = 'Debt components:';
					foreach ( $components as $comp ) {
							$val            = Custom_Fields::get_value( $id, $comp, $year );
							$lines[]        = '- ' . $comp . ': ' . var_export( $val, true );
							$component_sum += (float) $val;
					}

					$entries      = get_post_meta( $id, 'cdc_debt_adjustments', true );
					$adjust_total = 0.0;
					if ( is_array( $entries ) ) {
						foreach ( $entries as $e ) {
								$adjust_total += (float) $e['amount'];
						}
					}
					$lines[]    = '- adjustments: ' . $adjust_total;
					$calc_total = $component_sum + $adjust_total;
					$lines[]    = 'Calculated total debt: ' . $calc_total;
		}

		if ( '' !== $db_value && null !== $db_value ) {
				$replacement = $db_value;
		} elseif ( '' !== $backend_value && null !== $backend_value ) {
				$replacement = $backend_value;
		} elseif ( null !== $calc_total ) {
				$replacement = (string) $calc_total;
		}

		if ( '' !== $replacement && is_numeric( $replacement ) ) {
				self::reconcile_zero_value( $id, $field, $year, $replacement, $lines );
		}

				return array( $replacement, implode( "\n", $lines ) );
	}

		/**
		* Update stored values when a valid replacement is found to maintain a single source of truth.
		*
		* @param int    $id    Council post ID.
		* @param string $field Field name.
		* @param string $year  Financial year.
		* @param string $value Replacement value.
		* @param array  $lines Log lines for debugging.
		*/
	private static function reconcile_zero_value( int $id, string $field, string $year, string $value, array &$lines ): void {
		$current = Custom_Fields::get_value( $id, $field, $year );
		if ( (string) $current !== (string) $value ) {
				$lines[] = 'Reconciled stored value from ' . var_export( $current, true ) . ' to ' . $value . '.';
				Custom_Fields::update_value( $id, $field, $value, $year );
				update_post_meta( $id, $field . '_' . $year, $value );
				update_post_meta( $id, $field, $value );
		} else {
				$lines[] = 'Stored value already matches replacement.';
		}
	}

	/**
	 * Generate a short informative line for each counter.
	 */
	private static function counter_info( int $id, string $type, string $year ): string {
		$population = (float) Custom_Fields::get_value( $id, 'population', $year );
		$households = (float) Custom_Fields::get_value( $id, 'households', $year );

		switch ( $type ) {
			case 'debt':
				$debt     = (float) Custom_Fields::get_value( $id, 'total_debt', $year );
				$reserves = (float) Custom_Fields::get_value( $id, 'usable_reserves', $year );
				if ( $debt > 0 && $reserves > 0 ) {
					$ratio = ( $reserves / $debt ) * 100;
					return sprintf( __( 'Reserves to debt ratio: %s%%', 'council-debt-counters' ), number_format_i18n( $ratio, 1 ) );
				}
				break;
			case 'spending':
				$spend = (float) Custom_Fields::get_value( $id, 'annual_spending', $year );
				if ( $population > 0 && $spend > 0 ) {
					$per = $spend / $population;
					return sprintf( __( 'Spending per resident: £%s', 'council-debt-counters' ), number_format_i18n( $per, 2 ) );
				}
				break;
			case 'deficit':
				$deficit = (float) Custom_Fields::get_value( $id, 'annual_deficit', $year );
				if ( $population > 0 && $deficit != 0 ) {
					$per = $deficit / $population;
					return sprintf( __( 'Deficit per resident: £%s', 'council-debt-counters' ), number_format_i18n( $per, 2 ) );
				}
				break;
			case 'interest':
				$interest = (float) Custom_Fields::get_value( $id, 'interest_paid', $year );
				if ( $population > 0 && $interest > 0 ) {
					$per = $interest / $population;
					return sprintf( __( 'Interest per resident: £%s', 'council-debt-counters' ), number_format_i18n( $per, 2 ) );
				}
				break;
			case 'income':
				$income = (float) Custom_Fields::get_value( $id, 'total_income', $year );
				if ( $households > 0 && $income > 0 ) {
					$per = $income / $households;
					return sprintf( __( 'Income per household: £%s', 'council-debt-counters' ), number_format_i18n( $per, 2 ) );
				}
				break;
			default:
				$value = (float) Custom_Fields::get_value( $id, $type, $year );
				if ( $population > 0 && $value > 0 ) {
					$per = $value / $population;
					return sprintf( __( 'Per resident: £%s', 'council-debt-counters' ), number_format_i18n( $per, 2 ) );
				}
		}
		return '';
	}

		/**
		 * Renders a total annual counter for a specific field.
		 * @param string $field
		 * @param string $type
		 * @return bool|string
		 */
	private static function render_total_annual_counter( string $field, string $type = '' ) {
		$enabled = (array) get_option( 'cdc_enabled_counters', array() );
		if ( '' !== $type && ! in_array( $type, $enabled, true ) ) {
			return '';
		}

		$year           = self::total_counter_year( $type ?: $field );
				$annual = Custom_Fields::get_total_value( $field, $year );
				$rate   = Counter_Manager::per_second_rate( $annual );

			wp_enqueue_style( 'bootstrap-5' );
			wp_enqueue_style( 'cdc-counter' );
			wp_enqueue_style( 'cdc-counter-font' );
			wp_enqueue_script( 'font-awesome-kit' );
			wp_enqueue_script( 'bootstrap-5' );
			wp_enqueue_script( 'cdc-counter-animations' );

			$counter_id    = 'cdc-counter-total-' . sanitize_html_class( $field );
			$counter_class = 'cdc-counter-' . sanitize_html_class( $field );
			$obj           = Custom_Fields::get_field_by_name( $field );
			$label         = $obj && ! empty( $obj->label ) ? $obj->label : ucwords( str_replace( '_', ' ', $field ) );
			$title         = self::total_counter_title( $type ?: $field );
			$collapse_id   = 'cdc-detail-total-' . sanitize_html_class( $field );

			ob_start();
		?>
				<div class="cdc-counter-title text-center">
					<?php echo esc_html( $title ); ?>
						<button class="btn btn-link p-0 ms-2 cdc-info-btn" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
								<i class="fas fa-info-circle" aria-hidden="true"></i><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
						</button>
				</div>
								<div class="cdc-counter-wrapper text-center mb-3">
										<?php $duration = max( 1, (int) get_option( 'cdc_counter_duration', 15 ) ); ?>
										<div id="<?php echo esc_attr( $counter_id ); ?>" class="cdc-counter <?php echo esc_attr( $counter_class ); ?> display-6 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $annual ); ?>" data-growth="0" data-start="0" data-duration="<?php echo esc_attr( $duration ); ?>" data-prefix="£">
																&hellip;
												</div>
												<noscript>
																<p class="cdc-no-js alert alert-warning mb-0"><?php esc_html_e( 'You must enable JavaScript to see the counters', 'council-debt-counters' ); ?></p>
												</noscript>
								</div>
								<p class="text-muted small text-center mb-1"><?php printf( esc_html__( 'As reported in the %s financial year', 'council-debt-counters' ), esc_html( $year ) ); ?></p>
								<div class="collapse" id="<?php echo esc_attr( $collapse_id ); ?>">
						<ul class="mt-2 list-unstyled">
							<?php // translators: %s: Field label ?>
								<li><?php echo esc_html( sprintf( __( 'Annual %s:', 'council-debt-counters' ), $label ) ); ?> £<?php echo esc_html( number_format_i18n( $annual, 2 ) ); ?></li>
								<li><?php esc_html_e( 'Increase per second:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $rate, 6 ) ); ?></li>
						</ul>
						<div class="alert alert-warning mt-2">
							<?php esc_html_e( 'This counter assumes the annual figure is spread evenly from 1 April.', 'council-debt-counters' ); ?>
						</div>
				</div>
				<?php
				return ob_get_clean();
	}

	public static function render_total_spending_counter() {
			return self::render_total_annual_counter( 'annual_spending', 'spending' );
	}

	public static function render_total_deficit_counter() {
			return self::render_total_annual_counter( 'annual_deficit', 'deficit' );
	}

	public static function render_total_interest_counter() {
			return self::render_total_annual_counter( 'interest_paid', 'interest' );
	}

	public static function render_total_revenue_counter() {
			return self::render_total_annual_counter( 'total_income', 'income' );
	}

	public static function render_total_custom_counter( $atts ) {
			$type = sanitize_key( $atts['type'] ?? '' );
			$map  = array(
				'reserves'    => 'usable_reserves',
				'spending'    => 'annual_spending',
				'income'      => 'total_income',
				'deficit'     => 'annual_deficit',
				'interest'    => 'interest_paid',
				'consultancy' => 'consultancy_spend',
			);
			if ( ! isset( $map[ $type ] ) ) {
					return '';
			}
			return self::render_total_annual_counter( $map[ $type ], $type );
	}

	public static function render_total_debt_counter( $atts = array() ) {
		$atts        = shortcode_atts( array( 'year' => '' ), $atts );
		$year_param  = sanitize_text_field( $atts['year'] );
		$year        = ( '' !== $year_param && preg_match( '/^\d{4}\/\d{2}$/', $year_param ) ) ? $year_param : self::total_counter_year( 'debt' );
			$enabled = (array) get_option( 'cdc_enabled_counters', array() );
		if ( ! in_array( 'debt', $enabled, true ) ) {
				return '';
		}

			$posts                       = get_posts(
				array(
					'post_type'   => 'council',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);
						$total           = 0.0;
						$interest        = 0.0;
						$current_total   = 0.0;
						$long_term_total = 0.0;
						$lease_pfi_total = 0.0;
		foreach ( $posts as $id ) {
			if ( get_post_meta( (int) $id, 'cdc_parent_council', true ) ) {
								continue;
			}
				$total           += (float) Custom_Fields::get_value( (int) $id, 'total_debt', $year );
				$interest        += (float) Custom_Fields::get_value( (int) $id, 'interest_paid', $year );
				$current_total   += (float) Custom_Fields::get_value( (int) $id, 'current_liabilities', $year );
				$long_term_total += (float) Custom_Fields::get_value( (int) $id, 'long_term_liabilities', $year );
				$lease_pfi_total += (float) Custom_Fields::get_value( (int) $id, 'finance_lease_pfi_liabilities', $year );
		}

			$count = count(
				array_filter(
					$posts,
					function ( $cid ) {
						return ! get_post_meta( (int) $cid, 'cdc_parent_council', true );
					}
				)
			);

				$growth_per_second = $interest / ( 365 * 24 * 60 * 60 );

			wp_enqueue_style( 'bootstrap-5' );
			wp_enqueue_style( 'cdc-counter' );
			wp_enqueue_style( 'cdc-counter-font' );
			wp_enqueue_script( 'font-awesome-kit' );
			wp_enqueue_script( 'bootstrap-5' );
			wp_enqueue_script( 'cdc-counter-animations' );

			$collapse_id = 'cdc-detail-total-debt';
			$title       = self::total_counter_title( 'debt' );

			ob_start();
		?>
				<div class="cdc-counter-title text-center">
					<?php echo esc_html( $title ); ?>
						<button class="btn btn-link p-0 pb-1 cdc-info-btn" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
								<i class="fas fa-info-circle" aria-hidden="true"></i><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
						</button>
				</div>
								<div class="cdc-counter-wrapper text-center mb-3">
										<?php $duration = max( 1, (int) get_option( 'cdc_counter_duration', 15 ) ); ?>
										<div id="cdc-counter-total-debt" class="cdc-counter cdc-counter-debt display-4 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $total ); ?>" data-growth="0" data-start="0" data-duration="<?php echo esc_attr( $duration ); ?>" data-prefix="£">
																&hellip;
												</div>
												<noscript>
																<p class="cdc-no-js alert alert-warning mb-0"><?php esc_html_e( 'You must enable JavaScript to see the counters', 'council-debt-counters' ); ?></p>
												</noscript>
								</div>
								<p class="text-muted small text-center mb-1"><?php printf( esc_html__( 'As reported in the %s financial year', 'council-debt-counters' ), esc_html( $year ) ); ?></p>
								<div class="collapse text-center" id="<?php echo esc_attr( $collapse_id ); ?>">
												<ul class="mt-2 list-unstyled">
																<li><?php esc_html_e( 'Total Debt:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $total, 2 ) ); ?></li>
																<li><?php esc_html_e( 'Total Current Liabilities:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $current_total, 2 ) ); ?></li>
																<li><?php esc_html_e( 'Total Long Term Liabilities:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $long_term_total, 2 ) ); ?></li>
																<li><?php esc_html_e( 'Total PFI/Finance Lease Liabilities:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $lease_pfi_total, 2 ) ); ?></li>
																<li><?php esc_html_e( 'Growth rate per second:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $growth_per_second, 6 ) ); ?></li>
												</ul>
												<div class="text-muted">
														<?php
																printf(
																		/* translators: %s: number of councils */
																	esc_html__( 'Based on %s', 'council-debt-counters' ),
																	esc_html( sprintf( _n( '%d council', '%d councils', $count, 'council-debt-counters' ), $count ) )
																);
														?>
												</div>
				</div>
				<?php
				return ob_get_clean();
	}

	private static function leaderboard_html( string $type, int $limit, string $format, bool $with_link, string $year ) {
			$posts = get_posts(
				array(
					'post_type'   => 'council',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			$rows = array();
		foreach ( $posts as $id ) {
			if ( get_post_meta( $id, 'cdc_parent_council', true ) ) {
					continue;
			}

				$debt       = (float) Custom_Fields::get_value( $id, 'total_debt', $year );
				$population = (float) Custom_Fields::get_value( $id, 'population', $year );
				$reserves   = (float) Custom_Fields::get_value( $id, 'usable_reserves', $year );
				$spending   = (float) Custom_Fields::get_value( $id, 'annual_spending', $year );
				$income     = (float) Custom_Fields::get_value( $id, 'total_income', $year );
				$deficit    = (float) Custom_Fields::get_value( $id, 'annual_deficit', $year );
				$interest   = (float) Custom_Fields::get_value( $id, 'interest_paid', $year );

			switch ( $type ) {
				case 'highest_debt':
					$value = $debt;
					break;
				case 'debt_per_resident':
							$value = ( $population > 0 ) ? $debt / $population : null;
					break;
				case 'reserves_to_debt_ratio':
						$value = ( $debt > 0 ) ? $reserves / $debt : null;
					break;
				case 'biggest_deficit':
							$value = $deficit !== 0 ? $deficit : ( $spending - $income );
					break;
				case 'lowest_reserves':
						$value = $reserves;
					break;
				case 'highest_spending_per_resident':
						$value = ( $population > 0 ) ? $spending / $population : null;
					break;
				case 'highest_interest_paid':
						$value = $interest;
					break;
				default:
						$value = null;
			}

			if ( null === $value ) {
						continue;
			}

					$rows[] = array(
						'id'    => $id,
						'name'  => get_the_title( $id ),
						'value' => $value,
					);
		}

			$desc = ! in_array( $type, array( 'lowest_reserves', 'reserves_to_debt_ratio' ), true );

			usort(
				$rows,
				function ( $a, $b ) use ( $desc ) {
					if ( $a['value'] === $b['value'] ) {
							return 0;
					}
					if ( $desc ) {
							return ( $a['value'] < $b['value'] ) ? 1 : -1;
					}
					return ( $a['value'] < $b['value'] ) ? -1 : 1;
				}
			);

			$rows = array_slice( $rows, 0, $limit );

			ob_start();
		if ( 'list' === $format ) {
				echo '<ul class="list-group">';
			foreach ( $rows as $row ) {
					$label = in_array( $type, array( 'reserves_to_debt_ratio' ), true ) ? number_format_i18n( $row['value'], 2 ) . '%' : '£' . number_format_i18n( $row['value'], 2 );
					echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
					echo esc_html( $row['name'] );
					echo '<span class="badge bg-secondary">' . esc_html( $label ) . '</span>';
				if ( $with_link ) {
						echo ' <a class="ms-2" href="' . esc_url( get_permalink( $row['id'] ) ) . '">' . esc_html__( 'View details', 'council-debt-counters' ) . '</a>';
				}
					echo '</li>';
			}
				echo '</ul>';
		} else {
				echo '<table class="table table-striped">';
				echo '<thead><tr><th>' . esc_html__( 'Council', 'council-debt-counters' ) . '</th><th>' . esc_html__( 'Value', 'council-debt-counters' ) . '</th>';
			if ( $with_link ) {
					echo '<th></th>';
			}
				echo '</tr></thead><tbody>';
			foreach ( $rows as $row ) {
						$label = in_array( $type, array( 'reserves_to_debt_ratio' ), true ) ? number_format_i18n( $row['value'], 2 ) . '%' : '£' . number_format_i18n( $row['value'], 2 );
						echo '<tr><td>' . esc_html( $row['name'] ) . '</td><td>' . esc_html( $label ) . '</td>';
				if ( $with_link ) {
					echo '<td><a href="' . esc_url( get_permalink( $row['id'] ) ) . '">' . esc_html__( 'View details', 'council-debt-counters' ) . '</a></td>';
				}
						echo '</tr>';
			}
				echo '</tbody></table>';
		}
			return ob_get_clean();
	}

	public static function render_leaderboard( $atts ) {
			$atts = shortcode_atts(
				array(
					'type'   => 'highest_debt',
					'limit'  => 10,
					'format' => 'table',
					'link'   => '0',
					'year'   => '',
				),
				$atts
			);

			$type      = sanitize_key( $atts['type'] );
			$limit     = max( 1, intval( $atts['limit'] ) );
			$format    = in_array( $atts['format'], array( 'table', 'list' ), true ) ? $atts['format'] : 'table';
			$with_link = (bool) intval( $atts['link'] );
			$year      = sanitize_text_field( $atts['year'] );
		if ( '' === $year || ! preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
				$year = CDC_Utils::current_financial_year();
		}

			wp_enqueue_style( 'bootstrap-5' );
			wp_enqueue_style( 'cdc-year-overlay' );
			wp_enqueue_script( 'bootstrap-5' );
			wp_enqueue_script( 'cdc-leaderboard' );

			$nonce   = wp_create_nonce( Year_Selector::NONCE );
			$id_attr = 'cdc-leaderboard-' . md5( uniqid( '', true ) );

			ob_start();
		?>
				<div id="<?php echo esc_attr( $id_attr ); ?>" class="cdc-leaderboard" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-type="<?php echo esc_attr( $type ); ?>" data-limit="<?php echo esc_attr( $limit ); ?>" data-format="<?php echo esc_attr( $format ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
						<div class="cdc-year-selector mb-3 text-center">
								<label for="<?php echo esc_attr( $id_attr ); ?>-year" class="me-2"><?php esc_html_e( 'Financial Year', 'council-debt-counters' ); ?></label>
								<select id="<?php echo esc_attr( $id_attr ); ?>-year" class="form-select d-inline w-auto cdc-year-select">
									<?php foreach ( Docs_Manager::financial_years() as $y ) : ?>
												<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>><?php echo esc_html( $y ); ?></option>
										<?php endforeach; ?>
								</select>
						</div>
						<div class="cdc-leaderboard-container cdc-show">
							<?php echo self::leaderboard_html( $type, $limit, $format, $with_link, $year ); ?>
						</div>
				</div>
				<?php
				return ob_get_clean();
	}

	private static function render_counters_markup( int $id, string $year ) {
			$GLOBALS['cdc_selected_year'] = $year;
			$enabled                      = (array) get_option( 'cdc_enabled_counters', array() );
			$html                         = '';
		foreach ( $enabled as $type ) {
			switch ( $type ) {
				case 'debt':
						$html .= self::render_debt_counter( array( 'id' => $id ) );
					break;
				case 'spending':
						$html .= self::render_spending_counter( array( 'id' => $id ) );
					break;
				case 'income':
						$html .= self::render_revenue_counter( array( 'id' => $id ) );
					break;
				case 'deficit':
						$html .= self::render_deficit_counter( array( 'id' => $id ) );
					break;
				case 'interest':
						$html .= self::render_interest_counter( array( 'id' => $id ) );
					break;
				default:
						$html .= self::render_custom_counter(
							array(
								'id'   => $id,
								'type' => $type,
							)
						);
			}
		}
			unset( $GLOBALS['cdc_selected_year'] );
			return $html;
	}

	public static function render_council_counters( $atts ) {
			$id = CDC_Utils::resolve_council_id( $atts );
		if ( 0 === $id ) {
				return '';
		}
		if ( CDC_Utils::is_under_review( $id ) ) {
					return self::render_missing_prompt( array( 'id' => $id ) );
		}

				// Default to the most recent enabled year for this council.
				$year = CDC_Utils::latest_enabled_year( $id );

				wp_enqueue_style( 'bootstrap-5' );
				wp_enqueue_style( 'cdc-counter' );
				wp_enqueue_style( 'cdc-counter-font' );
				wp_enqueue_script( 'bootstrap-5' );
				wp_enqueue_script( 'cdc-counter-animations' );
				wp_enqueue_script( 'font-awesome-kit' );
				wp_enqueue_script( 'cdc-council-counters' );

				$nonce = wp_create_nonce( Year_Selector::NONCE );

				ob_start();
		?>
				<div class="cdc-council-counters" data-council-id="<?php echo esc_attr( $id ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
						<div class="cdc-year-selector mb-3 text-center">
								<label for="cdc-year-select-<?php echo esc_attr( $id ); ?>" class="me-2"><?php esc_html_e( 'Financial Year', 'council-debt-counters' ); ?></label>
								<select id="cdc-year-select-<?php echo esc_attr( $id ); ?>" class="form-select d-inline w-auto cdc-year-select">
									<?php foreach ( CDC_Utils::council_years( $id ) as $y ) : ?>
												<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>><?php echo esc_html( $y ); ?></option>
										<?php endforeach; ?>
								</select>
						</div>
						<div class="cdc-counters-container text-center">
							<?php echo self::render_counters_markup( $id, $year ); ?>
						</div>
				</div>
				<?php
				return ob_get_clean();
	}

	public static function ajax_render_counters() {
			check_ajax_referer( Year_Selector::NONCE, 'nonce' );
			$post_id = intval( $_POST['post_id'] ?? 0 );
			$year    = sanitize_text_field( $_POST['year'] ?? '' );
		if ( ! $post_id || '' === $year ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'council-debt-counters' ) ), 400 );
		}
			$post = get_post( $post_id );
		if ( ! $post || 'council' !== $post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Not found.', 'council-debt-counters' ) ), 404 );
		}
			$allowed = CDC_Utils::council_years( $post_id );
		if ( ! in_array( $year, $allowed, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid year.', 'council-debt-counters' ) ), 400 );
		}
			$html = self::render_counters_markup( $post_id, $year );
			wp_send_json_success( array( 'html' => $html ) );
	}

	public static function ajax_log_js() {
			check_ajax_referer( 'cdc_log_js', 'nonce' );
			$message = sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) );
		if ( $message ) {
				Error_Logger::log_info( 'JS: ' . $message );
		}
			wp_die();
	}

	public static function ajax_log_share() {
			check_ajax_referer( 'cdc_log_share', 'nonce' );
			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( $id ) {
				Stats_Page::log_share( $id );
		}
			wp_die();
	}

	public static function ajax_get_counter_value() {
			$id    = intval( $_POST['id'] ?? 0 );
			$field = sanitize_key( $_POST['field'] ?? '' );
			$year  = sanitize_text_field( $_POST['year'] ?? '' );
		if ( ! $id || '' === $field || '' === $year ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'council-debt-counters' ) ), 400 );
		}
			$post = get_post( $id );
		if ( ! $post || 'council' !== $post->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Not found.', 'council-debt-counters' ) ), 404 );
		}
			$value = Custom_Fields::get_value( $id, $field, $year );
			wp_send_json_success( array( 'value' => $value ) );
	}

	public static function ajax_render_leaderboard() {
			check_ajax_referer( Year_Selector::NONCE, 'nonce' );
			$type   = sanitize_key( $_POST['type'] ?? 'highest_debt' );
			$limit  = max( 1, intval( $_POST['limit'] ?? 10 ) );
			$format = in_array( $_POST['format'] ?? 'table', array( 'table', 'list' ), true ) ? sanitize_key( $_POST['format'] ) : 'table';
			$year   = sanitize_text_field( $_POST['year'] ?? '' );
		if ( '' === $year || ! preg_match( '/^\d{4}\/\d{2}$/', $year ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid year.', 'council-debt-counters' ) ), 400 );
		}
			$html = self::leaderboard_html( $type, $limit, $format, true, $year );
			wp_send_json_success( array( 'html' => $html ) );
	}
		/**
		* Provide an explanatory line for each counter type.
		*/
	private static function counter_description_text( string $type ): string {
		switch ( $type ) {
			case 'debt':
				return __( 'Shows the council\'s total outstanding borrowings for the selected year.', 'council-debt-counters' );
			case 'spending':
				return __( 'Total gross expenditure from the comprehensive income and expenditure statement for day-to-day services across all directorates.', 'council-debt-counters' );
			case 'deficit':
				return __( 'The deficit reported after grants, asset revaluation and pension adjustments.', 'council-debt-counters' );
			case 'income':
				return __( 'The income figure is the gross income declared on the comprehensive income and expenditure statement from directorate services. It excludes council tax.', 'council-debt-counters' );
			default:
				return __( 'Annual total for the selected financial year.', 'council-debt-counters' );
		}
	}
}
