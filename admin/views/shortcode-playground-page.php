<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$councils = get_posts([
    'post_type'   => 'council',
    'numberposts' => -1,
]);
$enabled = (array) get_option( 'cdc_enabled_counters', [] );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Shortcode Playground', 'council-debt-counters' ); ?></h1>
    <p><?php esc_html_e( 'Build and preview shortcodes for your councils.', 'council-debt-counters' ); ?></p>
    <div class="mb-3">
        <label for="cdc-play-council" class="form-label"><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></label>
        <select id="cdc-play-council" class="form-select">
            <?php foreach ( $councils as $c ) : ?>
                <option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="cdc-play-type" class="form-label"><?php esc_html_e( 'Counter Type', 'council-debt-counters' ); ?></label>
        <select id="cdc-play-type" class="form-select">
            <?php foreach ( $enabled as $type ) : ?>
                <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="cdc-play-shortcode" class="form-label"><?php esc_html_e( 'Shortcode', 'council-debt-counters' ); ?></label>
        <input type="text" id="cdc-play-shortcode" class="form-control" readonly>
    </div>
    <div id="cdc-play-preview" class="mt-4"></div>
</div>
