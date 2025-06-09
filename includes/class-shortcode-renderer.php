<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode_Renderer {

    public static function init() {
        add_shortcode( 'council_counter', [ __CLASS__, 'render_counter' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
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

        wp_enqueue_style( 'bootstrap-5' );
        wp_enqueue_style( 'cdc-counter' );
        wp_enqueue_script( 'bootstrap-5' );
        wp_enqueue_script( 'cdc-counter' );

        $details  = [
            'external_borrowing' => get_field( 'total_external_borrowing', $id ),
            'pwlb'               => get_field( 'pwlb_borrowing', $id ),
            'cfr'                => get_field( 'capital_financing_requirement', $id ),
        ];

        ob_start();
        ?>
        <div class="cdc-counter-wrapper mb-3">
            <div class="cdc-counter display-4 fw-bold" data-target="<?php echo esc_attr( $total ); ?>">£0</div>
            <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#cdc-detail-<?php echo esc_attr( $id ); ?>" aria-expanded="false" aria-controls="cdc-detail-<?php echo esc_attr( $id ); ?>">
                <?php esc_html_e( 'View details', 'council-debt-counters' ); ?>
            </button>
            <div class="collapse" id="cdc-detail-<?php echo esc_attr( $id ); ?>">
                <ul class="mt-2 list-unstyled">
                    <li><?php esc_html_e( 'Total External Borrowing:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['external_borrowing'], 0 ); ?></li>
                    <li><?php esc_html_e( 'PWLB Borrowing:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['pwlb'], 0 ); ?></li>
                    <li><?php esc_html_e( 'Capital Financing Requirement:', 'council-debt-counters' ); ?> £<?php echo number_format_i18n( (float) $details['cfr'], 0 ); ?></li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
