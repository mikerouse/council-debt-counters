<div class="wrap">
    <h1><?php esc_html_e( 'Council Debt Counters', 'council-debt-counters' ); ?></h1>
    <p><?php esc_html_e( 'This plugin requires the Advanced Custom Fields (ACF) plugin. Please ensure it is installed and activated.', 'council-debt-counters' ); ?></p>
    <p><?php esc_html_e( 'To get started, upload baseline debt figures or manually enter data for each council.', 'council-debt-counters' ); ?></p>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'council-debt-counters' );
        do_settings_sections( 'council-debt-counters' );
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>"><?php esc_html_e( 'License Key', 'council-debt-counters' ); ?></label></th>
                <td>
                    <input name="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>" type="text" id="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>" value="<?php echo esc_attr( License_Manager::get_license_key() ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Enter a valid license key to unlock unlimited councils.', 'council-debt-counters' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
if ( isset( $_GET['cdc_limit'] ) ) {
    echo '<div class="notice notice-warning"><p>' . esc_html__( 'The free version is limited to two councils. Enter a license key to add more.', 'council-debt-counters' ) . '</p></div>';
}
?>
