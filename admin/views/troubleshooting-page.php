<?php

// Access the error logger via its fully-qualified name to avoid namespace
// issues when this view is included from within a namespaced context.


if ( ! defined( 'ABSPATH' ) ) exit;

$log = \CouncilDebtCounters\Error_Logger::get_log();

if ( isset( $_POST['cdc_clear_log'] ) ) {
    \CouncilDebtCounters\Error_Logger::clear_log();
    $log = \CouncilDebtCounters\Error_Logger::get_log();
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Log cleared.', 'council-debt-counters' ) . '</p></div>';
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Troubleshooting', 'council-debt-counters' ); ?></h1>
    <p><?php esc_html_e( 'View recent plugin errors for debugging purposes.', 'council-debt-counters' ); ?></p>
    <form method="post">
        <textarea readonly rows="20" style="width:100%;" aria-label="<?php echo esc_attr__( 'Error log', 'council-debt-counters' ); ?>"><?php echo esc_textarea( $log ); ?></textarea>
        <p><button type="submit" name="cdc_clear_log" class="button"><?php esc_html_e( 'Clear Log', 'council-debt-counters' ); ?></button></p>
    </form>
</div>
