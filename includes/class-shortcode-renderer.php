<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Renderer {

	private static function get_council_id_from_atts( array $atts ) {
			$id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;

		if ( 0 === $id && ! empty( $atts['council'] ) ) {
				$name = sanitize_text_field( $atts['council'] );
				$post = get_page_by_title( $name, OBJECT, 'council' );

			if ( ! $post ) {
					$post = get_page_by_path( sanitize_title( $name ), OBJECT, 'council' );
			}

			if ( $post ) {
						$id = $post->ID;
			}
		}

			return $id;
	}

	private static function render_annual_counter( int $id, string $field, string $type = '', bool $with_details = true ) {
		$enabled = (array) get_option( 'cdc_enabled_counters', array() );
		if ( '' !== $type && ! in_array( $type, $enabled, true ) ) {
			return '';
		}
		$raw_value = Custom_Fields::get_value( $id, $field );
		if ( '' === $raw_value || null === $raw_value ) {
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
						__( 'No %s figure found for this council. Please set this value in the admin area.', 'council-debt-counters' ),
						$label
					)
				)
			);
		}
		$annual  = (float) $raw_value;
		$rate    = Counter_Manager::per_second_rate( $annual );
		$current = $rate * Counter_Manager::seconds_since_fy_start();

		wp_enqueue_style( 'bootstrap-5' );
		wp_enqueue_style( 'cdc-counter' );
		wp_enqueue_style( 'cdc-counter-font' );
		wp_enqueue_script( 'bootstrap-5' );
		wp_enqueue_script( 'cdc-counter-animations' );

		$counter_id    = 'cdc-counter-' . $id . '-' . sanitize_html_class( $field );
		$counter_class = 'cdc-counter-' . sanitize_html_class( $field );
		$obj           = Custom_Fields::get_field_by_name( $field );
		$label         = $obj && ! empty( $obj->label ) ? $obj->label : ucwords( str_replace( '_', ' ', $field ) );
		$collapse_id   = 'cdc-detail-' . $id . '-' . sanitize_html_class( $field );
		ob_start();
		?>
		<div class="cdc-counter-wrapper text-center mb-3">
			<div id="<?php echo esc_attr( $counter_id ); ?>" class="cdc-counter <?php echo esc_attr( $counter_class ); ?> display-6 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $current ); ?>" data-growth="<?php echo esc_attr( $rate ); ?>" data-start="<?php echo esc_attr( $current ); ?>" data-prefix="£">
                                &hellip;
			</div>
			<?php if ( $with_details ) : ?>
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                                <span aria-hidden="true">&#x2139;</span><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
                        </button>
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
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function init() {
		add_shortcode( 'council_counter', array( __CLASS__, 'render_counter' ) );
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
                add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
                add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
                add_action( 'wp_ajax_cdc_log_js', array( __CLASS__, 'ajax_log_js' ) );
                add_action( 'wp_ajax_nopriv_cdc_log_js', array( __CLASS__, 'ajax_log_js' ) );
                add_action( 'wp_ajax_cdc_log_share', array( __CLASS__, 'ajax_log_share' ) );
                add_action( 'wp_ajax_nopriv_cdc_log_share', array( __CLASS__, 'ajax_log_share' ) );
        }

	public static function register_assets() {
		$plugin_file = dirname( __DIR__ ) . '/council-debt-counters.php';
		$use_cdn     = apply_filters( 'cdc_use_cdn', false );

		if ( $use_cdn ) {
			$bootstrap_css = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css';
			$bootstrap_js  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js';
			$countup_js    = 'https://cdn.jsdelivr.net/npm/countup.js@2.6.2/dist/countUp.umd.js';
		} else {
			$bootstrap_css = plugins_url( 'public/css/bootstrap.min.css', $plugin_file );
			$bootstrap_js  = plugins_url( 'public/js/bootstrap.bundle.min.js', $plugin_file );
			$countup_js    = plugins_url( 'public/js/countUp.umd.js', $plugin_file );
		}

		wp_register_style( 'cdc-counter', plugins_url( 'public/css/counter.css', $plugin_file ), array(), '0.1.0' );
		$font     = get_option( 'cdc_counter_font', 'Oswald' );
		$weight   = get_option( 'cdc_counter_weight', '600' );
		$font_url = 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $font ) . ':wght@' . $weight . '&display=swap';
		wp_register_style( 'cdc-counter-font', $font_url, array(), null );
		wp_add_inline_style( 'cdc-counter-font', ".cdc-counter{font-family:'{$font}',sans-serif;font-weight:{$weight};}" );
		wp_register_script( 'countup', $countup_js, array(), '2.6.2', true );
                wp_register_script( 'cdc-counter-animations', plugins_url( 'public/js/counter-animations.js', $plugin_file ), array( 'countup' ), '0.1.0', true );
                wp_register_script( 'cdc-share-tracking', plugins_url( 'public/js/share-tracking.js', $plugin_file ), array(), '0.1.0', true );
                wp_register_style( 'bootstrap-5', $bootstrap_css, array(), '5.3.1' );
		wp_register_script( 'bootstrap-5', $bootstrap_js, array(), '5.3.1', true );
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

	public static function render_counter( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'type' => 'debt',
			),
			$atts
		);
		$id   = intval( $atts['id'] );
		$type = sanitize_key( $atts['type'] );
		if ( 0 === $id ) {
			return '';
		}
		if ( '' !== $type && 'debt' !== $type ) {
			// Delegate to the custom counter handler for non-debt types
			return self::render_custom_counter( $atts );
		}
		$enabled = (array) get_option( 'cdc_enabled_counters', array() );
		if ( ! in_array( 'debt', $enabled, true ) ) {
			return '';
		}

		$total = Custom_Fields::get_value( $id, 'total_debt' );
		if ( ! $total ) {
			$total = 0;
		}
		$interest          = (float) Custom_Fields::get_value( $id, 'interest_paid_on_debt' );
		$growth_per_second = $interest / ( 365 * 24 * 60 * 60 );

		// Council balance sheets cover the year ending 31 March.
		// Calculations therefore start on 1 April.
		$year     = gmdate( 'Y' );
		$now      = time();
		$fy_start = strtotime( "$year-04-01" );
		if ( $now < $fy_start ) {
			// If before 1 April, use previous year
			$fy_start = strtotime( ( $year - 1 ) . '-04-01' );
		}
		$elapsed_seconds = max( 0, $now - $fy_start );
		$start_value     = $total + ( $growth_per_second * $elapsed_seconds * -1 );

		wp_enqueue_style( 'bootstrap-5' );
		wp_enqueue_style( 'cdc-counter' );
		wp_enqueue_style( 'cdc-counter-font' );
		wp_enqueue_script( 'bootstrap-5' );
		wp_enqueue_script( 'cdc-counter-animations' );

		$details = array(
			'interest'           => $interest,
			'counter_start_date' => null,
		);

		// Get band property counts
		$bands = array(
			'A' => (int) Custom_Fields::get_value( $id, 'band_a_properties' ),
			'B' => (int) Custom_Fields::get_value( $id, 'band_b_properties' ),
			'C' => (int) Custom_Fields::get_value( $id, 'band_c_properties' ),
			'D' => (int) Custom_Fields::get_value( $id, 'band_d_properties' ),
			'E' => (int) Custom_Fields::get_value( $id, 'band_e_properties' ),
			'F' => (int) Custom_Fields::get_value( $id, 'band_f_properties' ),
			'G' => (int) Custom_Fields::get_value( $id, 'band_g_properties' ),
			'H' => (int) Custom_Fields::get_value( $id, 'band_h_properties' ),
		);

                $population = (int) Custom_Fields::get_value( $id, 'population' );

                $reserves = (float) Custom_Fields::get_value( $id, 'usable_reserves' );
                $reserves_ratio = ( $reserves > 0 && $total > 0 ) ? round( ( $reserves / $total ) * 100, 1 ) : null;

		$debt_repayment_explainer = __( 'Growth uses the annual interest figure from the latest accounts. Actual borrowing and repayments may differ.', 'council-debt-counters' );

		$collapse_id = 'cdc-detail-' . $id . '-debt';
		ob_start();
		?>
		<div class="cdc-counter-wrapper text-center mb-3">
                        <div id="<?php echo esc_attr( 'cdc-counter-' . $id . '-debt' ); ?>" class="cdc-counter cdc-counter-debt display-4 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $total + ( $growth_per_second * $elapsed_seconds ) ); ?>" data-growth="<?php echo esc_attr( $growth_per_second ); ?>" data-start="<?php echo esc_attr( $start_value ); ?>" data-prefix="£">
                                &hellip;
                        </div>
                        <noscript>
                                <p class="cdc-no-js alert alert-warning mb-0"><?php esc_html_e( 'You must enable JavaScript to see the counters', 'council-debt-counters' ); ?></p>
                        </noscript>
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                                <span aria-hidden="true">&#x2139;</span><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
                        </button>
			<div class="collapse" id="<?php echo esc_attr( $collapse_id ); ?>">
                                <ul class="mt-2 list-unstyled">
                                                <li><?php esc_html_e( 'Interest Paid (annual):', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( (float) $details['interest'], 2 ) ); ?></li>
                                                <li><?php esc_html_e( 'Net growth/reduction per second:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $growth_per_second, 6 ) ); ?></li>
                                                <?php if ( null !== $reserves_ratio ) : ?>
                                                <li><?php esc_html_e( 'Reserves to Debt Ratio:', 'council-debt-counters' ); ?> <?php echo esc_html( number_format_i18n( $reserves_ratio, 1 ) ); ?>%</li>
                                                <li class="small text-muted"><?php esc_html_e( 'A lower ratio indicates a higher reliance on borrowing relative to savings.', 'council-debt-counters' ); ?></li>
                                                <?php endif; ?>
                                </ul>
				<?php if ( array_sum( $bands ) > 0 ) : ?>
					<h5><?php esc_html_e( 'Debt per property by Council Tax Band:', 'council-debt-counters' ); ?></h5>
					<ul class="mt-2 list-unstyled">
					<?php
					foreach ( $bands as $band => $count ) :
						if ( $count > 0 ) :
							$debt_per_property = $total / $count;
							?>
							<?php // translators: 1: Council tax band letter, 2: Debt per property ?>
						<li><?php echo esc_html( sprintf( 'Band %s: £%s per property', $band, number_format_i18n( $debt_per_property, 2 ) ) ); ?></li>
							<?php
					endif;
endforeach;
					?>
					</ul>
				<?php endif; ?>
				<?php if ( $population > 0 ) : ?>
				<h5><?php esc_html_e( 'Debt per person:', 'council-debt-counters' ); ?></h5>
				<ul class="mt-2 list-unstyled">
					<?php // translators: %s: Debt per person ?>
					<li><?php echo esc_html( sprintf( '£%s per person', number_format_i18n( $total / $population, 2 ) ) ); ?></li>
				</ul>
				<?php endif; ?>
				<?php if ( $debt_repayment_explainer ) : ?>
				<div class="alert alert-info mt-2">
					<?php echo esc_html( $debt_repayment_explainer ); ?>
				</div>
				<?php endif; ?>
				<div class="alert alert-warning mt-2">
					<?php esc_html_e( 'Total debt = Current Liabilities + Long Term Liabilities + Finance Lease/PFI Liabilities + Adjustments. Growth is estimated using the latest annual interest figure spread evenly across the year. Borrowing or repayments may change the real total, so treat this as a guide.', 'council-debt-counters' ); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_spending_counter( $atts ) {
		$id = self::get_council_id_from_atts( $atts );
		if ( 0 === $id ) {
			return '';
		}
		return self::render_annual_counter( $id, 'annual_spending', 'spending' );
	}

	public static function render_deficit_counter( $atts ) {
		$id = self::get_council_id_from_atts( $atts );
		if ( 0 === $id ) {
			return '';
		}
		return self::render_annual_counter( $id, 'annual_deficit', 'deficit' );
	}

	public static function render_interest_counter( $atts ) {
		$id = self::get_council_id_from_atts( $atts );
		if ( 0 === $id ) {
			return '';
		}
		return self::render_annual_counter( $id, 'interest_paid', 'interest' );
	}

	public static function render_revenue_counter( $atts ) {
		$id = self::get_council_id_from_atts( $atts );
		if ( 0 === $id ) {
			return '';
		}
		return self::render_annual_counter( $id, 'total_income', 'income' );
	}

        public static function render_custom_counter( $atts ) {
                $id   = self::get_council_id_from_atts( $atts );
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
                $id = self::get_council_id_from_atts( $atts );
                if ( 0 === $id ) {
                        return '';
                }

                $name     = get_the_title( $id );
                $interest  = (float) Custom_Fields::get_value( $id, 'interest_paid_on_debt' );
                $debt      = (float) Custom_Fields::get_value( $id, 'total_debt' );
                $permalink = get_permalink();

                if ( $interest > 0 ) {
                        $message = sprintf( __( '%1$s spends £%2$s a year on debt interest. Find out more:', 'council-debt-counters' ), $name, number_format_i18n( $interest, 1 ) );
                } else {
                        $message = sprintf( __( '%1$s’s debt is £%2$s. See how it compares:', 'council-debt-counters' ), $name, number_format_i18n( $debt, 0 ) );
                }

                $encoded = rawurlencode( $message . ' ' . $permalink );

                wp_enqueue_style( 'bootstrap-5' );
                wp_enqueue_script( 'bootstrap-5' );
                wp_enqueue_script( 'cdc-share-tracking' );
                wp_localize_script( 'cdc-share-tracking', 'cdcShare', array(
                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'cdc_log_share' ),
                ) );

                ob_start();
                ?>
                <div class="cdc-share-buttons mt-3">
                        <div class="fw-bold mb-1"><?php esc_html_e( 'Share this', 'council-debt-counters' ); ?></div>
                        <a class="btn btn-outline-primary btn-sm me-2 cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="twitter" target="_blank" rel="noopener noreferrer" href="https://x.com/intent/tweet?text=<?php echo esc_attr( $encoded ); ?>">
                                X
                        </a>
                        <a class="btn btn-outline-success btn-sm me-2 cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="whatsapp" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?php echo esc_attr( $encoded ); ?>">
                                WhatsApp
                        </a>
                        <a class="btn btn-outline-primary btn-sm cdc-share-link" data-council-id="<?php echo esc_attr( $id ); ?>" data-share-type="facebook" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( rawurlencode( $permalink ) ); ?>">
                                Facebook
                        </a>
                </div>
                <?php
                return ob_get_clean();
        }

        private static function render_total_annual_counter( string $field, string $type = '' ) {
                $enabled = (array) get_option( 'cdc_enabled_counters', array() );
                if ( '' !== $type && ! in_array( $type, $enabled, true ) ) {
                        return '';
                }

                $annual  = Custom_Fields::get_total_value( $field );
                $rate    = Counter_Manager::per_second_rate( $annual );
                $current = $rate * Counter_Manager::seconds_since_fy_start();

                wp_enqueue_style( 'bootstrap-5' );
                wp_enqueue_style( 'cdc-counter' );
                wp_enqueue_style( 'cdc-counter-font' );
                wp_enqueue_script( 'bootstrap-5' );
                wp_enqueue_script( 'cdc-counter-animations' );

                $counter_id    = 'cdc-counter-total-' . sanitize_html_class( $field );
                $counter_class = 'cdc-counter-' . sanitize_html_class( $field );
                $obj           = Custom_Fields::get_field_by_name( $field );
                $label         = $obj && ! empty( $obj->label ) ? $obj->label : ucwords( str_replace( '_', ' ', $field ) );
                $collapse_id   = 'cdc-detail-total-' . sanitize_html_class( $field );

                ob_start();
                ?>
                <div class="cdc-counter-wrapper text-center mb-3">
                        <div id="<?php echo esc_attr( $counter_id ); ?>" class="cdc-counter <?php echo esc_attr( $counter_class ); ?> display-6 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $current ); ?>" data-growth="<?php echo esc_attr( $rate ); ?>" data-start="<?php echo esc_attr( $current ); ?>" data-prefix="£">
                                &hellip;
                        </div>
                        <noscript>
                                <p class="cdc-no-js alert alert-warning mb-0"><?php esc_html_e( 'You must enable JavaScript to see the counters', 'council-debt-counters' ); ?></p>
                        </noscript>
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                                <span aria-hidden="true">&#x2139;</span><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
                        </button>
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
                return self::render_total_annual_counter( $map[ $type ], $type );
        }

        public static function render_total_debt_counter() {
                $enabled = (array) get_option( 'cdc_enabled_counters', array() );
                if ( ! in_array( 'debt', $enabled, true ) ) {
                        return '';
                }

                $posts = get_posts([
                        'post_type'   => 'council',
                        'numberposts' => -1,
                        'fields'      => 'ids',
                ]);
                $total    = 0.0;
                $interest = 0.0;
                foreach ( $posts as $id ) {
                        $total    += (float) Custom_Fields::get_value( (int) $id, 'total_debt' );
                        $interest += (float) Custom_Fields::get_value( (int) $id, 'interest_paid_on_debt' );
                }
          
                $count = count( $posts );

                $growth_per_second = $interest / ( 365 * 24 * 60 * 60 );

                $year     = gmdate( 'Y' );
                $now      = time();
                $fy_start = strtotime( "$year-04-01" );
                if ( $now < $fy_start ) {
                        $fy_start = strtotime( ( $year - 1 ) . '-04-01' );
                }
                $elapsed_seconds = max( 0, $now - $fy_start );
                $start_value     = $total + ( $growth_per_second * $elapsed_seconds * -1 );

                wp_enqueue_style( 'bootstrap-5' );
                wp_enqueue_style( 'cdc-counter' );
                wp_enqueue_style( 'cdc-counter-font' );
                wp_enqueue_script( 'bootstrap-5' );
                wp_enqueue_script( 'cdc-counter-animations' );

                $collapse_id = 'cdc-detail-total-debt';

                ob_start();
                ?>
                <div class="cdc-counter-wrapper text-center mb-3">
                        <div id="cdc-counter-total-debt" class="cdc-counter cdc-counter-debt display-4 fw-bold" role="status" aria-live="polite" data-target="<?php echo esc_attr( $total + ( $growth_per_second * $elapsed_seconds ) ); ?>" data-growth="<?php echo esc_attr( $growth_per_second ); ?>" data-start="<?php echo esc_attr( $start_value ); ?>" data-prefix="£">
                                &hellip;
                        </div>
                        <noscript>
                                <p class="cdc-no-js alert alert-warning mb-0"><?php esc_html_e( 'You must enable JavaScript to see the counters', 'council-debt-counters' ); ?></p>
                        </noscript>
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                                <span aria-hidden="true">&#x2139;</span><span class="visually-hidden"><?php esc_html_e( 'View details', 'council-debt-counters' ); ?></span>
                        </button>
                        <div class="collapse" id="<?php echo esc_attr( $collapse_id ); ?>">
                                <ul class="mt-2 list-unstyled">
                                        <li><?php esc_html_e( 'Interest Paid (annual):', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $interest, 2 ) ); ?></li>
                                        <li><?php esc_html_e( 'Net growth/reduction per second:', 'council-debt-counters' ); ?> £<?php echo esc_html( number_format_i18n( $growth_per_second, 6 ) ); ?></li>
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
                </div>
                <?php
                return ob_get_clean();
        }

        public static function render_leaderboard( $atts ) {
                $atts = shortcode_atts(
                        array(
                                'type'   => 'highest_debt',
                                'limit'  => 10,
                                'format' => 'table',
                                'link'   => '0',
                        ),
                        $atts
                );

                $type   = sanitize_key( $atts['type'] );
                $limit  = max( 1, intval( $atts['limit'] ) );
                $format = in_array( $atts['format'], array( 'table', 'list' ), true ) ? $atts['format'] : 'table';
                $with_link = (bool) intval( $atts['link'] );

                $posts = get_posts(
                        array(
                                'post_type'   => 'council',
                                'numberposts' => -1,
                                'fields'      => 'ids',
                        )
                );

                $rows = array();
                foreach ( $posts as $id ) {
                        $data = array();
                        $debt      = (float) Custom_Fields::get_value( $id, 'total_debt' );
                        $population = (float) Custom_Fields::get_value( $id, 'population' );
                        $reserves  = (float) Custom_Fields::get_value( $id, 'usable_reserves' );
                        $spending  = (float) Custom_Fields::get_value( $id, 'annual_spending' );
                        $income    = (float) Custom_Fields::get_value( $id, 'total_income' );
                        $deficit   = (float) Custom_Fields::get_value( $id, 'annual_deficit' );
                        $interest  = (float) Custom_Fields::get_value( $id, 'interest_paid' );

                        switch ( $type ) {
                                case 'highest_debt':
                                        $value = $debt;
                                        break;
                                case 'debt_per_resident':
                                        $value = ( $population > 0 ) ? $debt / $population : null;
                                        break;
                                case 'debt_to_reserves_ratio':
                                        $value = ( $reserves > 0 ) ? $debt / $reserves : null;
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

                $desc = true;
                if ( 'lowest_reserves' === $type ) {
                        $desc = false;
                }

                usort(
                        $rows,
                        function ( $a, $b ) use ( $desc ) {
                                if ( $a['value'] === $b['value'] ) {
                                        return 0;
                                }
                                if ( $desc ) {
                                        return ( $a['value'] < $b['value'] ) ? 1 : -1;
                                } else {
                                        return ( $a['value'] < $b['value'] ) ? -1 : 1;
                                }
                        }
                );

                $rows = array_slice( $rows, 0, $limit );

                wp_enqueue_style( 'bootstrap-5' );
                wp_enqueue_script( 'bootstrap-5' );

                ob_start();
                if ( 'list' === $format ) {
                        echo '<ul class="list-group">';
                        foreach ( $rows as $row ) {
                                $label = ( in_array( $type, array( 'debt_to_reserves_ratio' ), true ) ) ? number_format_i18n( $row['value'], 2 ) . '%' : '£' . number_format_i18n( $row['value'], 2 );
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
                                $label = ( in_array( $type, array( 'debt_to_reserves_ratio' ), true ) ) ? number_format_i18n( $row['value'], 2 ) . '%' : '£' . number_format_i18n( $row['value'], 2 );
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
}
