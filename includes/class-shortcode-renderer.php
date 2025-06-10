<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode_Renderer {

    public static function init() {
        add_shortcode( 'council_counter', [ __CLASS__, 'render_counter' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
    }

    public static function register_assets() {
        wp_register_style( 'cdc-counter', plugins_url( 'public/css/counter.css', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0' );
        wp_register_script( 'cdc-counter', plugins_url( 'public/js/counter.js', dirname( __DIR__ ) . '/council-debt-counters.php' ), [], '0.1.0', true );
        wp_register_style( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css', [], '5.3.1' );
        wp_register_script( 'bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js', [], '5.3.1', true );
    }

    public static function render_counter( $atts ) {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $id   = intval( $atts['id'] );
        if ( ! $id ) {
            return '';
        }

        $total = get_field( 'total_debt', $id );
        if ( ! $total ) {
            $total = 0;
        }
        $interest = (float) get_field( 'interest_paid_on_debt', $id );
        $mrp = (float) get_field( 'minimum_revenue_provision', $id );
        $net_growth_per_year = $interest - $mrp;
        $growth_per_second = $net_growth_per_year / (365 * 24 * 60 * 60);

        // UK Financial Year Start Date is 1 April
        $year = date('Y');
        $now = time();
        $fy_start = strtotime("$year-04-01");
        if ( $now < $fy_start ) {
            // If before 1 April, use previous year
            $fy_start = strtotime(($year - 1) . '-04-01');
        }
        $elapsed_seconds = max(0, $now - $fy_start);
        $start_value = $total + ($growth_per_second * $elapsed_seconds * -1);

        wp_enqueue_style( 'bootstrap-5' );
        wp_enqueue_style( 'cdc-counter' );
        wp_enqueue_script( 'bootstrap-5' );
        wp_enqueue_script( 'cdc-counter' );

        $details  = [
            'external_borrowing' => get_field( 'total_external_borrowing', $id ),
            'pwlb'               => get_field( 'pwlb_borrowing', $id ),
            'cfr'                => get_field( 'capital_financing_requirement', $id ),
            'interest'           => $interest,
            'mrp'                => $mrp,
            'counter_start_date' => null, // removed, always 6 April
        ];

        // Get band property counts
        $bands = [
            'A' => (int) get_field( 'band_a_properties', $id ),
            'B' => (int) get_field( 'band_b_properties', $id ),
            'C' => (int) get_field( 'band_c_properties', $id ),
            'D' => (int) get_field( 'band_d_properties', $id ),
            'E' => (int) get_field( 'band_e_properties', $id ),
            'F' => (int) get_field( 'band_f_properties', $id ),
            'G' => (int) get_field( 'band_g_properties', $id ),
            'H' => (int) get_field( 'band_h_properties', $id ),
        ];

        $population = (int) get_field( 'population', $id );

        $mrp = (float) get_field( 'minimum_revenue_provision', $id );
        $debt_repayment_explainer = '';
        if ( $mrp > 0 ) {
            $principal_repayment = $mrp - $interest;
            if ( $principal_repayment > 0 ) {
                $years_to_clear = ceil( $total / $principal_repayment );
                $years_no_interest = ceil( $total / $mrp );
                $debt_repayment_explainer = sprintf(
                    /* translators: 1: years to clear debt, 2: principal per year, 3: interest per year, 4: years if no interest */
                    __( 'At the current rate of debt repayment (Minimum Revenue Provision), the council pays £%2$s per year towards the debt and £%3$s per year in interest. At this rate, it would take approximately %1$d years to clear the debt, assuming no new borrowing and constant repayments/interest. If all interest payments stopped tomorrow, it would take about %4$d years to clear the debt at the same repayment rate.', 'council-debt-counters' ),
                    $years_to_clear,
                    number_format_i18n( $principal_repayment, 0 ),
                    number_format_i18n( $interest, 0 ),
                    $years_no_interest
                );
            } else {
                $debt_repayment_explainer = sprintf(
                    /* translators: 1: interest per year, 2: repayment per year */
                    __( 'At the current rate of debt repayment (Minimum Revenue Provision), the council pays £%2$s per year towards the debt but pays £%1$s per year in interest. This means the debt will never be paid off, as repayments are not even covering the interest.', 'council-debt-counters' ),
                    number_format_i18n( $interest, 0 ),
                    number_format_i18n( $mrp, 0 )
                );
                if ( $mrp > 0 ) {
                    $years_no_interest = ceil( $total / $mrp );
                    $debt_repayment_explainer .= ' ' . sprintf(
                        /* translators: %d: years if no interest */
                        __( 'If all interest payments stopped tomorrow, it would take about %d years to clear the debt at the same repayment rate.', 'council-debt-counters' ),
                        $years_no_interest
                    );
                }
            }
        }

        ob_start();
        ?>
        <div class="cdc-counter-wrapper mb-3">
            <div class="cdc-counter display-4 fw-bold" data-target="<?php echo esc_attr( $total + ($growth_per_second * $elapsed_seconds) ); ?>" data-growth="<?php echo esc_attr( $growth_per_second ); ?>" data-start="<?php echo esc_attr( $start_value ); ?>">
                £0
            </div>
            <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#cdc-detail-<?php echo esc_attr( $id ); ?>" aria-expanded="false" aria-controls="cdc-detail-<?php echo esc_attr( $id ); ?>">
                <?php esc_html_e( 'View details', 'council-debt-counters' ); ?>
            </button>
            <div class="collapse" id="cdc-detail-<?php echo esc_attr( $id ); ?>">
                <ul class="mt-2 list-unstyled">
                    <li><?php esc_html_e( 'Total External Borrowing:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['external_borrowing'], 0 ); ?></li>
                    <li><?php esc_html_e( 'PWLB Borrowing:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['pwlb'], 0 ); ?></li>
                    <li><?php esc_html_e( 'Capital Financing Requirement:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['cfr'], 0 ); ?></li>
                    <li><?php esc_html_e( 'Interest Paid on Debt (annual):', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['interest'], 0 ); ?></li>
                    <li><?php esc_html_e( 'Minimum Revenue Provision (annual):', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['mrp'], 0 ); ?></li>
                    <li><?php esc_html_e( 'Net growth/reduction per second:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( $growth_per_second, 6 ); ?></li>
                </ul>
                <h5><?php esc_html_e( 'Debt per property by Council Tax Band:', 'council-debt-counters' ); ?></h5>
                <ul class="mt-2 list-unstyled">
                <?php foreach ( $bands as $band => $count ) :
                    if ( $count > 0 ) :
                        $debt_per_property = $total / $count;
                ?>
                    <li><?php echo esc_html( sprintf( 'Band %s: £%s per property', $band, number_format_i18n( $debt_per_property, 0 ) ) ); ?></li>
                <?php endif; endforeach; ?>
                </ul>
                <?php if ( $population > 0 ) : ?>
                <h5><?php esc_html_e( 'Debt per person:', 'council-debt-counters' ); ?></h5>
                <ul class="mt-2 list-unstyled">
                    <li><?php echo esc_html( sprintf( '£%s per person', number_format_i18n( $total / $population, 0 ) ) ); ?></li>
                </ul>
                <?php endif; ?>
                <?php if ( $debt_repayment_explainer ) : ?>
                <div class="alert alert-info mt-2">
                    <?php echo esc_html( $debt_repayment_explainer ); ?>
                </div>
                <?php endif; ?>
                <div class="alert alert-warning mt-2">
                    <?php esc_html_e( 'Total debt is calculated as: Total External Borrowing + Adjustments + Manual Entry (if any). PWLB and CFR are shown for reference only. Interest is not added to the debt figure. This counter is an estimate. It assumes the council will pay the same amount of interest on its debt as last year, spread evenly over the year. In reality, the council could pay off debt faster or slower, refinance at a different rate, or borrow more. The actual interest paid will only be known when the next set of financial statements is published. This is just a live estimate, not an official figure.', 'council-debt-counters' ); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
