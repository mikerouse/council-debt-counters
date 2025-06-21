<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sharing_Meta {
    public static function init() {
        add_action( 'wp_head', [ __CLASS__, 'output_meta' ] );
    }

    public static function output_meta() {
        if ( ! is_singular( 'council' ) ) {
            return;
        }
        $post_id = get_the_ID();
        $img_id  = absint( get_post_meta( $post_id, 'cdc_sharing_image', true ) );
        if ( ! $img_id ) {
            $img_id = absint( get_option( 'cdc_default_sharing_thumbnail', 0 ) );
        }
        if ( $img_id ) {
            $url = wp_get_attachment_url( $img_id );
            if ( $url ) {
                printf( "<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $url ) );
                printf( "<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url( $url ) );
            }
        }
    }
}
