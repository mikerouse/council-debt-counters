<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenAI_Helper {
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public static function query( string $prompt, string $model = '' ) {
        $api_key = get_option( 'cdc_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return new \WP_Error( 'missing_key', __( 'OpenAI API key not configured.', 'council-debt-counters' ) );
        }

        if ( empty( $model ) ) {
            $model = get_option( 'cdc_openai_model', 'gpt-3.5-turbo' );
        }

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
            'timeout' => 20,
        ];

        $response = wp_remote_post( self::API_ENDPOINT, $args );
        if ( is_wp_error( $response ) ) {
            Error_Logger::log( 'OpenAI API request failed: ' . $response->get_error_message() );
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }

        Error_Logger::log( 'OpenAI API unexpected response: ' . $body );
        return new \WP_Error( 'invalid_response', __( 'Unexpected response from OpenAI API.', 'council-debt-counters' ) );
    }
}
