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
        add_action( 'wp_ajax_cdc_ai_field', [ __CLASS__, 'ajax_ai_field' ] );
        add_action( 'wp_ajax_cdc_ai_clarify_field', [ __CLASS__, 'ajax_clarify_field' ] );
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

    private static function clarify_field_prompt( string $council, string $field ) {
        $prompt = sprintf(
            'We want to retrieve a single numeric figure for "%s" relating to %s council. Suggest a short question to ask an AI so it returns a number and a source URL. Reply only with the question.',
            $field,
            $council
        );
        $response = self::query( $prompt );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        return trim( is_array( $response ) ? $response['content'] : $response );
    }

    private static function ask_field_value( string $council, string $field, string $prompt = '' ) {
        Error_Logger::log_info( 'AI field request: ' . $field . ' for ' . $council );
        if ( empty( $prompt ) ) {
            $prompt = sprintf(
                'What is the most recent figure for %s for %s council in pounds? Respond only with JSON: {"value":number,"source":"URL"}. Prefer sources from .gov.uk domains.',
                $field,
                $council
            );
        }
        $response = self::query( $prompt );
        if ( is_wp_error( $response ) ) {
            Error_Logger::log_error( 'AI field error: ' . $response->get_error_message() );
            return $response;
        }
        $content = is_array( $response ) ? $response['content'] : $response;
        $tokens  = is_array( $response ) && isset( $response['tokens'] ) ? intval( $response['tokens'] ) : 0;
        $data    = json_decode( $content, true );
        if ( is_array( $data ) && isset( $data['value'] ) ) {
            Error_Logger::log_info( 'AI field result: ' . $field . ' = ' . $data['value'] . ' tokens ' . $tokens );
            return [ 'value' => $data['value'], 'source' => $data['source'] ?? '', 'tokens' => $tokens ];
        }

        if ( preg_match( '/([0-9][0-9,\.]*)/', $content, $m ) ) {
            $value = floatval( str_replace( ',', '', $m[1] ) );
            $source = '';
            if ( preg_match( '#https?://\S+#', $content, $s ) ) {
                $source = rtrim( $s[0], ".,'\"" );
            }
            Error_Logger::log_info( 'AI field parsed text: ' . $field . ' = ' . $value . ' tokens ' . $tokens );
            return [ 'value' => $value, 'source' => $source, 'tokens' => $tokens ];
        }

        Error_Logger::log_error( 'AI field parse error: ' . $content );
        return new \WP_Error( 'invalid_ai', __( 'Unexpected AI response.', 'council-debt-counters' ) );
    }

    public static function ajax_clarify_field() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ], 403 );
        }
        $field = sanitize_text_field( $_POST['field'] ?? '' );
        $cid   = intval( $_POST['council_id'] ?? 0 );
        $name  = sanitize_text_field( $_POST['council_name'] ?? '' );
        if ( ! $name && $cid ) {
            $name = get_the_title( $cid );
        }
        if ( ! $name || ! $field ) {
            wp_send_json_error( [ 'message' => __( 'Missing data.', 'council-debt-counters' ) ] );
        }
        $label = $field;
        $f = Custom_Fields::get_field_by_name( $field );
        if ( $f ) {
            $label = $f->label;
        }
        $prompt = self::clarify_field_prompt( $name, $label );
        if ( is_wp_error( $prompt ) ) {
            wp_send_json_error( [ 'message' => $prompt->get_error_message() ] );
        }
        wp_send_json_success( [ 'prompt' => $prompt ] );
    }

    public static function ajax_ai_field() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'council-debt-counters' ) ], 403 );
        }
        $field = sanitize_text_field( $_POST['field'] ?? '' );
        $cid   = intval( $_POST['council_id'] ?? 0 );
        $name  = sanitize_text_field( $_POST['council_name'] ?? '' );
        if ( ! $name && $cid ) {
            $name = get_the_title( $cid );
        }
        if ( ! $name || ! $field ) {
            wp_send_json_error( [ 'message' => __( 'Missing data.', 'council-debt-counters' ) ] );
        }

        Error_Logger::log_debug( 'AJAX ask field "' . $field . '" for ' . $name . ' (ID ' . $cid . ')' );

        $label = $field;
        $f = Custom_Fields::get_field_by_name( $field );
        if ( $f ) {
            $label = $f->label;
        }
        $user_prompt = sanitize_text_field( $_POST['prompt'] ?? '' );
        $result = self::ask_field_value( $name, $label, $user_prompt );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( $result );
    }
}
