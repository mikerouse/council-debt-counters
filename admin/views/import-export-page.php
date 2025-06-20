<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Import & Export', 'council-debt-counters' ); ?></h1>
    <h2><?php esc_html_e( 'Import Councils', 'council-debt-counters' ); ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'cdc_load_csv', 'cdc_load_csv_nonce' ); ?>
        <input type="file" name="cdc_csv_file" accept=".csv,.json" required />
        <button type="submit" class="button"><?php esc_html_e( 'Import', 'council-debt-counters' ); ?></button>
    </form>
    <p class="description">
        <?php esc_html_e( 'CSV/JSON must include a "council_name" column plus any custom field names.', 'council-debt-counters' ); ?>
    </p>
    <h2><?php esc_html_e( 'Export Councils', 'council-debt-counters' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'cdc_export', 'cdc_export_nonce' ); ?>
        <select name="format">
            <option value="csv">CSV</option>
            <option value="json">JSON</option>
        </select>
        <button type="submit" name="cdc_export" class="button button-primary"><?php esc_html_e( 'Download', 'council-debt-counters' ); ?></button>
    </form>
    <h2 class="mt-4"><?php esc_html_e( 'Export Settings', 'council-debt-counters' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'cdc_export_settings', 'cdc_export_settings_nonce' ); ?>
        <button type="submit" name="cdc_export_settings" class="button button-primary"><?php esc_html_e( 'Download Settings', 'council-debt-counters' ); ?></button>
    </form>

    <h2 class="mt-4"><?php esc_html_e( 'Import Settings', 'council-debt-counters' ); ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'cdc_import_settings', 'cdc_import_settings_nonce' ); ?>
        <input type="file" name="cdc_settings_file" accept=".json" required />
        <button type="submit" class="button"><?php esc_html_e( 'Import Settings', 'council-debt-counters' ); ?></button>
    </form>
</div>
