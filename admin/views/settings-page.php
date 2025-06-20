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
        <?php settings_fields( 'cdc_settings' ); ?>
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
            <tr>
                <th scope="row"><label for="cdc_openai_model"><?php esc_html_e( 'OpenAI Model', 'council-debt-counters' ); ?></label></th>
                <td>
                    <?php $model = get_option( 'cdc_openai_model', 'gpt-3.5-turbo' ); ?>
                    <select name="cdc_openai_model" id="cdc_openai_model">
                        <option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>gpt-3.5-turbo</option>
                        <option value="gpt-4" <?php selected( $model, 'gpt-4' ); ?>>gpt-4</option>
                        <option value="o3" <?php selected( $model, 'o3' ); ?>>o3</option>
                        <option value="o4-mini" <?php selected( $model, 'o4-mini' ); ?>>o4-mini</option>
                        <option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>gpt-4o</option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Requires an OpenAI API key on the Licences & Addons page.', 'council-debt-counters' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
