<?php
use CouncilDebtCounters\License_Manager;
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Licence Keys and Addons', 'council-debt-counters' ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'cdc_license' );
        do_settings_sections( 'cdc_license' );
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>"><?php esc_html_e( 'License Key', 'council-debt-counters' ); ?></label></th>
                <td>
                    <input name="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>" type="text" id="<?php echo esc_attr( License_Manager::OPTION_KEY ); ?>" value="<?php echo esc_attr( License_Manager::get_license_key() ); ?>" class="regular-text" />
                    <button type="button" id="cdc-check-license" class="button" style="margin-left:10px;">
                        <?php esc_html_e( 'Check License', 'council-debt-counters' ); ?>
                    </button>
                    <p id="cdc-license-result" class="description">
                        <?php echo License_Manager::is_valid() ? esc_html__( 'License is valid.', 'council-debt-counters' ) : ''; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'council-debt-counters' ); ?></label></th>
                <td>
                    <input name="cdc_openai_api_key" type="text" id="cdc_openai_api_key" value="<?php echo esc_attr( get_option( 'cdc_openai_api_key', '' ) ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Optional: provide an OpenAI API key to assist with generating council information.', 'council-debt-counters' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_recaptcha_site_key"><?php esc_html_e( 'reCAPTCHA Site Key', 'council-debt-counters' ); ?></label></th>
                <td>
                    <input name="cdc_recaptcha_site_key" type="text" id="cdc_recaptcha_site_key" value="<?php echo esc_attr( get_option( 'cdc_recaptcha_site_key', '' ) ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cdc_recaptcha_secret_key"><?php esc_html_e( 'reCAPTCHA Secret Key', 'council-debt-counters' ); ?></label></th>
                <td>
                    <input name="cdc_recaptcha_secret_key" type="text" id="cdc_recaptcha_secret_key" value="<?php echo esc_attr( get_option( 'cdc_recaptcha_secret_key', '' ) ); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
