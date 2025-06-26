<?php
use CouncilDebtCounters\Custom_Fields;
use CouncilDebtCounters\CDC_Utils;

if ( ! defined( 'ABSPATH' ) ) exit;

$councils = get_posts([
    'post_type'   => 'council',
    'numberposts' => -1,
    'post_status' => [ 'publish', 'draft', 'under_review' ],
    'orderby'     => 'title',
    'order'       => 'ASC',
]);
$cid = isset( $_POST['cdc_council'] ) ? intval( $_POST['cdc_council'] ) : 0;
$fields = Custom_Fields::get_fields();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Calculations', 'council-debt-counters' ); ?></h1>
    <form method="post" class="mb-4">
        <?php wp_nonce_field( 'cdc_calc_action', 'cdc_calc_nonce' ); ?>
        <label for="cdc_council" class="screen-reader-text"><?php esc_html_e( 'Select council', 'council-debt-counters' ); ?></label>
        <select name="cdc_council" id="cdc_council">
            <?php foreach ( $councils as $c ) : ?>
                <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $cid, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="cdc_calc_action">
            <option value="move_2025_to_2023"><?php esc_html_e( 'Move financial data from 2025/26 to 2023/24', 'council-debt-counters' ); ?></option>
            <option value="check_zero"><?php esc_html_e( 'Check for 0 values', 'council-debt-counters' ); ?></option>
        </select>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Run', 'council-debt-counters' ); ?></button>
    </form>
    <?php if ( $cid ) : ?>
    <h2><?php echo esc_html( get_the_title( $cid ) ); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Field', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( '2023/24', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( '2025/26', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $fields as $f ) : ?>
                <tr>
                    <td><?php echo esc_html( $f->label ); ?></td>
                    <td><?php echo esc_html( Custom_Fields::get_value( $cid, $f->name, '2023/24' ) ); ?></td>
                    <td><?php echo esc_html( Custom_Fields::get_value( $cid, $f->name, '2025/26' ) ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
