<?php

// Access the error logger via its fully-qualified name to avoid namespace
// issues when this view is included from within a namespaced context.


if ( ! defined( 'ABSPATH' ) ) exit;

$log = \CouncilDebtCounters\Error_Logger::get_log();

$log_level = get_option( 'cdc_log_level', 'standard' );

if ( isset( $_POST['cdc_save_log_level'] ) && check_admin_referer( 'cdc_save_log_level' ) ) {
    $level = sanitize_key( $_POST['cdc_log_level'] ?? '' );
    if ( ! in_array( $level, [ 'verbose', 'standard', 'quiet' ], true ) ) {
        $level = 'standard';
    }
    update_option( 'cdc_log_level', $level );
    $log_level = $level;
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Logging level saved.', 'council-debt-counters' ) . '</p></div>';
}

if ( isset( $_POST['cdc_clear_log'] ) ) {
    \CouncilDebtCounters\Error_Logger::clear_log();
    $log = \CouncilDebtCounters\Error_Logger::get_log();
    \CouncilDebtCounters\Error_Logger::log_info( 'Troubleshooting log cleared' );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Log cleared.', 'council-debt-counters' ) . '</p></div>';
}

if ( isset( $_POST['cdc_download_log'] ) ) {
    header( 'Content-Type: text/plain' );
    header( 'Content-Disposition: attachment; filename=troubleshooting.log' );
    echo $log;
    exit;
}

if ( isset( $_POST['cdc_migrate_acf_nonce'] ) && wp_verify_nonce( $_POST['cdc_migrate_acf_nonce'], 'cdc_migrate_acf' ) ) {
    \CouncilDebtCounters\Custom_Fields::migrate_from_meta();
    echo '<div class="notice notice-success"><p>' . esc_html__( 'ACF data migrated.', 'council-debt-counters' ) . '</p></div>';
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Troubleshooting', 'council-debt-counters' ); ?></h1>
    <p><?php esc_html_e( 'View recent plugin errors for debugging purposes.', 'council-debt-counters' ); ?></p>
    <form method="post">
        <textarea readonly rows="20" style="width:100%;" aria-label="<?php echo esc_attr__( 'Error log', 'council-debt-counters' ); ?>"><?php echo esc_textarea( $log ); ?></textarea>
        <p>
            <button type="submit" name="cdc_clear_log" class="button"><?php esc_html_e( 'Clear Log', 'council-debt-counters' ); ?></button>
            <button type="submit" name="cdc_download_log" class="button" style="margin-left:10px;"><?php esc_html_e( 'Download Log', 'council-debt-counters' ); ?></button>
        </p>
    </form>
    <form method="post">
        <?php wp_nonce_field( 'cdc_migrate_acf', 'cdc_migrate_acf_nonce' ); ?>
        <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Migrate ACF Data', 'council-debt-counters' ); ?></button></p>
    </form>

    <form method="post" class="mt-4">
        <?php wp_nonce_field( 'cdc_save_log_level' ); ?>
        <h2><?php esc_html_e( 'JavaScript Logging Level', 'council-debt-counters' ); ?></h2>
        <label for="cdc_log_level" class="screen-reader-text"><?php esc_html_e( 'Select logging level', 'council-debt-counters' ); ?></label>
        <select name="cdc_log_level" id="cdc_log_level">
            <option value="verbose" <?php selected( $log_level, 'verbose' ); ?>><?php esc_html_e( 'Verbose', 'council-debt-counters' ); ?></option>
            <option value="standard" <?php selected( $log_level, 'standard' ); ?>><?php esc_html_e( 'Standard', 'council-debt-counters' ); ?></option>
            <option value="quiet" <?php selected( $log_level, 'quiet' ); ?>><?php esc_html_e( 'Quiet', 'council-debt-counters' ); ?></option>
        </select>
        <?php submit_button( __( 'Save Logging Level', 'council-debt-counters' ), 'primary', 'cdc_save_log_level' ); ?>
    </form>
</div>
