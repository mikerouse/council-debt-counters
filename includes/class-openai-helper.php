<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenAI_Helper {
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * Approximate tokens per minute limits per model.
     * These values are conservative defaults and can be filtered.
     */
    private static $tpm_limits = [
        'gpt-3.5-turbo' => 10000,
        'gpt-4'         => 10000,
        'o3'            => 20000,
        'o4-mini'       => 200000,
        'gpt-4o'        => 30000,
    ];

    private static function throttle( int $tokens, string $model ) {
        $limits = apply_filters( 'cdc_openai_tpm_limits', self::$tpm_limits );
        $limit  = isset( $limits[ $model ] ) ? intval( $limits[ $model ] ) : 10000;

        $state = get_transient( 'cdc_openai_tpm_state' );
        if ( ! is_array( $state ) || time() > $state['reset'] ) {
            $state = [ 'tokens' => 0, 'reset' => time() + 60 ];
        }

        if ( $state['tokens'] + $tokens > $limit ) {
            $wait = max( 0, $state['reset'] - time() );
            if ( $wait > 0 ) {
                sleep( $wait );
            }
            $state = [ 'tokens' => 0, 'reset' => time() + 60 ];
        }

        $state['tokens'] += $tokens;
        set_transient( 'cdc_openai_tpm_state', $state, 120 );
    }


    public static function init() {
        add_action( 'wp_ajax_cdc_check_openai_key', [ __CLASS__, 'ajax_check_key' ] );
    }

    public static function query( string $prompt, string $model = '' ) {
        $api_key = get_option( 'cdc_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            Error_Logger::log( 'OpenAI API key missing' );
            return new \WP_Error( 'missing_key', __( 'OpenAI API key not configured.', 'council-debt-counters' ) );
        }

        if ( empty( $model ) ) {
            $model = get_option( 'cdc_openai_model', 'gpt-3.5-turbo' );
        }

        $estimate = (int) ceil( strlen( $prompt ) / 4 ) + 500;
        self::throttle( $estimate, $model );

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode([
                'model' => $model,
                'messages' => [
                    [ 'role' => 'user', 'content' => $prompt ]
                ],
            ]),
            'timeout' => apply_filters( 'cdc_openai_timeout', 60 ),
        ];

        $response = wp_remote_post( self::API_ENDPOINT, $args );
        if ( is_wp_error( $response ) ) {
            Error_Logger::log( 'OpenAI API request failed: ' . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        if ( 429 === $code ) {
            if ( preg_match( '/try again in ([0-9\.]+)s/i', $body, $m ) ) {
                $delay = (int) ceil( floatval( $m[1] ) );
                sleep( $delay );
                $response = wp_remote_post( self::API_ENDPOINT, $args );
                $code     = wp_remote_retrieve_response_code( $response );
                $body     = wp_remote_retrieve_body( $response );
            }
        }

        $data = json_decode( $body, true );
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            $tokens = $data['usage']['total_tokens'] ?? 0;
            if ( $tokens > $estimate ) {
                self::throttle( $tokens - $estimate, $model );
            }
            return [
                'content' => $data['choices'][0]['message']['content'],
                'tokens'  => $tokens,
            ];
        }

        Error_Logger::log( 'OpenAI API unexpected response: ' . $body );
        return new \WP_Error( 'invalid_response', __( 'Unexpected response from OpenAI API.', 'council-debt-counters' ) );
    }

    /**
     * Test an API key by making a minimal request.
     */
    public static function test_key( string $key ) {
        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $key,
            ],
            'body'    => wp_json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [ [ 'role' => 'user', 'content' => 'Hello' ] ],
                'max_tokens' => 1,
            ]),
            'timeout' => apply_filters( 'cdc_openai_timeout', 60 ),
        ];

        $response = wp_remote_post( self::API_ENDPOINT, $args );
        if ( is_wp_error( $response ) ) {
            Error_Logger::log( 'OpenAI key test failed: ' . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            return true;
        }

        Error_Logger::log( 'OpenAI key test http ' . $code . ': ' . wp_remote_retrieve_body( $response ) );
        return new \WP_Error( 'invalid_key', __( 'OpenAI API key appears invalid.', 'council-debt-counters' ) );
    }

    public static function ajax_check_key() {
        check_ajax_referer( 'cdc_check_openai', 'nonce' );
        $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
        if ( empty( $key ) ) {
            wp_send_json_error( [ 'message' => __( 'API key missing.', 'council-debt-counters' ) ] );
        }

        $result = self::test_key( $key );
        if ( true === $result ) {
            wp_send_json_success( [ 'message' => __( 'API key is valid.', 'council-debt-counters' ) ] );
        }

        $msg = is_wp_error( $result ) ? $result->get_error_message() : __( 'API key test failed.', 'council-debt-counters' );
        wp_send_json_error( [ 'message' => $msg ] );
    }
}
