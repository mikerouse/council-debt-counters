<?php
$enabled = (array) get_option( 'cdc_enabled_counters', [] );
$titles  = (array) get_option( 'cdc_counter_titles', [] );
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
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Enable', 'council-debt-counters' ); ?></th>
                                <th><?php esc_html_e( 'Tab', 'council-debt-counters' ); ?></th>
                                <th><?php esc_html_e( 'Counter Title', 'council-debt-counters' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $types as $key => $label ) : ?>
                                <tr>
                                    <td><input type="checkbox" name="cdc_enabled_counters[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?> /></td>
                                    <td><?php echo esc_html( $label ); ?></td>
                                    <td><input type="text" name="cdc_counter_titles[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $titles[ $key ] ?? $label ); ?>" class="regular-text" /></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            <tr>
                <th scope="row"><label for="cdc_counter_font"><?php esc_html_e( 'Counter Font', 'council-debt-counters' ); ?></label></th>
                <td>
                    <?php $font = get_option( 'cdc_counter_font', 'Oswald' ); ?>
                    <select name="cdc_counter_font" id="cdc_counter_font">
                        <?php
                        foreach ( \CouncilDebtCounters\Settings_Page::FONT_CHOICES as $f ) {
                            printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $f ), selected( $font, $f, false ) );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_counter_weight"><?php esc_html_e( 'Font Weight', 'council-debt-counters' ); ?></label></th>
                <td>
                    <?php $weight = get_option( 'cdc_counter_weight', '600' ); ?>
                    <input type="number" name="cdc_counter_weight" id="cdc_counter_weight" value="<?php echo esc_attr( $weight ); ?>" min="100" max="900" step="100" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Default Sharing Thumbnail', 'council-debt-counters' ); ?></th>
                <td>
                    <?php $thumb = absint( get_option( 'cdc_default_sharing_thumbnail', 0 ) ); ?>
                    <div id="cdc-default-thumbnail-preview">
                        <?php if ( $thumb ) : ?>
                            <?php echo wp_get_attachment_image( $thumb, array( 150, 150 ) ); ?>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="cdc-default-thumbnail" name="cdc_default_sharing_thumbnail" value="<?php echo esc_attr( $thumb ); ?>" data-url="<?php echo esc_url( $thumb ? wp_get_attachment_url( $thumb ) : '' ); ?>" />
                    <button type="button" class="button" id="cdc-default-thumbnail-button"><?php esc_html_e( 'Select Image', 'council-debt-counters' ); ?></button>
                    <button type="button" class="button" id="cdc-default-thumbnail-remove" <?php if ( ! $thumb ) echo 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'council-debt-counters' ); ?></button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_use_cdn_assets"><?php esc_html_e( 'Load assets from CDN (Bootstrap, Font Awesome)', 'council-debt-counters' ); ?></label></th>
                <td>
                    <?php $cdn = get_option( 'cdc_use_cdn_assets', 0 ); ?>
                    <input type="checkbox" id="cdc_use_cdn_assets" name="cdc_use_cdn_assets" value="1" <?php checked( $cdn, 1 ); ?> />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
