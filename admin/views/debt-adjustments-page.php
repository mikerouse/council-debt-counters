<?php
use CouncilDebtCounters\Debt_Adjustments_Page;

if ( ! defined( 'ABSPATH' ) ) exit;

$councils = get_posts([
    'post_type'   => 'council',
    'numberposts' => -1,
    'post_status' => [ 'publish', 'draft' ],
]);

$success = '';
$error   = '';

if ( isset( $_POST['cdc_add_adjustment'] ) && isset( $_POST['cdc_council_id'] ) ) {
    if ( ! check_admin_referer( 'cdc_add_adjustment', 'cdc_adjust_nonce', false ) ) {
        $error = __( 'Security check failed.', 'council-debt-counters' );
    } else {
        $cid    = intval( $_POST['cdc_council_id'] );
        $amount = floatval( $_POST['cdc_amount'] );
        $note   = sanitize_text_field( $_POST['cdc_note'] );
        Debt_Adjustments_Page::add_adjustment( $cid, $amount, $note );
        $success = __( 'Adjustment saved.', 'council-debt-counters' );
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Debt Adjustments', 'council-debt-counters' ); ?></h1>
    <?php if ( $success ) : ?>
        <div class="notice notice-success"><p><?php echo esc_html( $success ); ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>
    <form method="post" class="mb-4">
        <?php wp_nonce_field( 'cdc_add_adjustment', 'cdc_adjust_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="cdc_council_id"><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></label></th>
                <td>
                    <select name="cdc_council_id" id="cdc_council_id">
                        <?php foreach ( $councils as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( get_the_title( $c ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_amount"><?php esc_html_e( 'Amount', 'council-debt-counters' ); ?></label></th>
                <td><input type="number" name="cdc_amount" id="cdc_amount" step="0.01" required class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_note"><?php esc_html_e( 'Note', 'council-debt-counters' ); ?></label></th>
                <td><input type="text" name="cdc_note" id="cdc_note" class="regular-text" /></td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary" name="cdc_add_adjustment"><?php esc_html_e( 'Add Adjustment', 'council-debt-counters' ); ?></button></p>
    </form>
</div>
