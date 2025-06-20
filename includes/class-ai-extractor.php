<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Extractor {
    /**
     * Approximate characters per token when chunking text.
     */
    const AVG_TOKEN_CHARS = 4;

    /**
     * Maximum tokens sent to OpenAI in a single request.
     * This leaves headroom for the model's limits and response size.
     */
    const MAX_TOKENS_PER_REQUEST = 12000;

    /**
     * Ask OpenAI to extract key figures from a statement of accounts text.
     * Large documents are automatically split into manageable chunks.
     *
     * @param string $text Raw text from the statement of accounts.
     * @return mixed WP_Error on failure or array of extracted values.
     */
    public static function extract_key_figures( string $text ) {
        $max_chars = self::MAX_TOKENS_PER_REQUEST * self::AVG_TOKEN_CHARS;
        if ( strlen( $text ) <= $max_chars ) {
            $data = self::process_chunk( $text );
            return self::finalise_data( $data );
        }

        $results = [];
        for ( $offset = 0; $offset < strlen( $text ); $offset += $max_chars ) {
            $chunk   = substr( $text, $offset, $max_chars );
            $data    = self::process_chunk( $chunk );
            if ( is_wp_error( $data ) ) {
                return $data;
            }
            foreach ( $data as $field => $value ) {
                if ( ! empty( $value ) && ! isset( $results[ $field ] ) && floatval( $value ) != 0 ) {
                    $results[ $field ] = $value;
                }
            }
        }

        return self::finalise_data( $results );
    }

    /**
     * Send a single chunk of text to OpenAI and decode the JSON response.
     */
    private static function process_chunk( string $text ) {
        $prompt = "You are analysing a UK council's statement of accounts. "
            . "Return ONLY a JSON object with these keys: "
            . "current_liabilities, long_term_liabilities, finance_lease_pfi_liabilities, "
            . "interest_paid_on_debt, minimum_revenue_provision, annual_spending, total_income, "
            . "annual_deficit, interest_paid, usable_reserves, consultancy_spend. "
            . "Use 0 if a figure is not mentioned. Numbers should be digits without commas or currency symbols."
            . "\n" . $text;

        $response = OpenAI_Helper::query( $prompt );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( $response, true );
        if ( is_array( $data ) ) {
            return $data;
        }

        Error_Logger::log( 'AI extraction parse error: ' . $response );
        return new \WP_Error( 'invalid_ai_response', __( 'Failed to parse AI response.', 'council-debt-counters' ) );
    }

    /**
     * Ensure all expected keys exist and calculate deficit if needed.
     */
    private static function finalise_data( $data ) {
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $fields = [
            'current_liabilities',
            'long_term_liabilities',
            'finance_lease_pfi_liabilities',
            'interest_paid_on_debt',
            'minimum_revenue_provision',
            'annual_spending',
            'total_income',
            'annual_deficit',
            'interest_paid',
            'usable_reserves',
            'consultancy_spend',
        ];

        $results = [];
        foreach ( $fields as $field ) {
            $results[ $field ] = isset( $data[ $field ] ) ? $data[ $field ] : 0;
        }

        if ( isset( $results['annual_spending'], $results['total_income'] ) && ! isset( $results['annual_deficit'] ) ) {
            $results['annual_deficit'] = floatval( $results['annual_spending'] ) - floatval( $results['total_income'] );
        }

        return $results;
    }
}
