<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$enabled = (array) get_option( 'cdc_enabled_counters', [] );
$types = [
    'debt' => __( 'Debt', 'council-debt-counters' ),
    'spending' => __( 'Spending', 'council-debt-counters' ),
    'income' => __( 'Income', 'council-debt-counters' ),
    'deficit' => __( 'Deficit', 'council-debt-counters' ),
    'interest' => __( 'Interest', 'council-debt-counters' ),
    'reserves' => __( 'Reserves', 'council-debt-counters' ),
    'consultancy' => __( 'Consultancy', 'council-debt-counters' ),
];
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Settings', 'council-debt-counters' ); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'council-debt-counters' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enabled Counters', 'council-debt-counters' ); ?></th>
                <td>
                    <?php foreach ( $types as $key => $label ) : ?>
                        <label style="display:block;margin-bottom:4px;">
                            <input type="checkbox" name="cdc_enabled_counters[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?> />
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
