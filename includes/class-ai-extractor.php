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
    const MAX_TOKENS_PER_REQUEST = 8000;

    /**
     * Tokens used in the last extraction run.
     *
     * @var int
     */
    private static $last_tokens = 0;

    /**
     * Extract paragraphs likely to contain relevant figures.
     *
     * @param string $text Raw text from the statement of accounts.
     * @return string Filtered text.
     */
    private static function extract_relevant_sections( string $text ) : string {
        $patterns = [
            '/interest(\s+paid)?/i',
            '/borrowing/i',
            '/finance\s*lease/i',
            '/pfi\s*liabilit(?:y|ies)/i',
            '/long[- ]?term\s*liabilit(?:y|ies)/i',
            '/current\s*liabilit(?:y|ies)/i',
            '/gross\s*expenditure/i',
            '/gross\s*income/i',
            '/net\s*cost\s*of\s*services/i',
            '/deficit\s*on\s*provision\s*of\s*services/i',
            '/comprehensive\s*income/i',
            '/usable\s*reserves?/i',
            '/consultanc(?:y|ies)/i',
            '/external\s*contractors?/i',
            '/professional\s*fees?/i',
            '/minimum\s*revenue\s*provision/i',
            '/financing\s*and\s*investment\s*income/i',
            '/public\s*works\s*loan\s*board/i',
        ];

        $paragraphs = preg_split( '/\r?\n\s*\r?\n/', $text );
        if ( empty( $paragraphs ) ) {
            $paragraphs = [ $text ];
        }

        $matches = [];
        foreach ( $paragraphs as $para ) {
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $para ) ) {
                    $matches[] = trim( $para );
                    break;
                }
            }
        }

        $filtered = implode( "\n\n", $matches );
        if ( empty( $filtered ) ) {
            $filtered = $text;
        }

        Error_Logger::log_debug( 'Prefilter reduced text from ' . strlen( $text ) . ' to ' . strlen( $filtered ) . ' chars using ' . count( $matches ) . ' sections' );

        return $filtered;
    }

    /**
     * Ask OpenAI to extract key figures from a statement of accounts text.
     * Large documents are automatically split into manageable chunks.
     *
     * @param string $text Raw text from the statement of accounts.
     * @return mixed WP_Error on failure or array of extracted values.
     */
    public static function extract_key_figures( string $text ) {
        self::$last_tokens = 0;

        $filtered_text = self::extract_relevant_sections( $text );

        $max_chars = self::MAX_TOKENS_PER_REQUEST * self::AVG_TOKEN_CHARS;
        if ( strlen( $filtered_text ) <= $max_chars ) {
            $chunk = self::process_chunk( $filtered_text );
            if ( is_wp_error( $chunk ) ) {
                return $chunk;
            }
            self::$last_tokens += $chunk['tokens'];
            return self::finalise_data( $chunk['data'] );
        }

        $results = [];
        $length = strlen( $filtered_text );
        for ( $offset = 0; $offset < $length; $offset += $max_chars ) {
            $chunk_result = self::process_chunk( substr( $filtered_text, $offset, $max_chars ) );
            if ( is_wp_error( $chunk_result ) ) {
                return $chunk_result;
            }
            self::$last_tokens += $chunk_result['tokens'];
            foreach ( $chunk_result['data'] as $field => $value ) {
                if ( ! empty( $value ) && ! isset( $results[ $field ] ) && floatval( $value ) !== 0 ) {
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
            . "interest_paid, annual_spending, total_income, "
            . "annual_deficit, usable_reserves, consultancy_spend. "
            . "Use 0 if a figure is not mentioned. Numbers should be digits without commas or currency symbols. "
            . "If the document shows figures in thousands of pounds (e.g. £000s), multiply them by 1000 before returning the values." 
            . "\n" . $text;

        $response = OpenAI_Helper::query( $prompt );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = is_array( $response ) && isset( $response['content'] ) ? $response['content'] : $response;
        $tokens  = is_array( $response ) && isset( $response['tokens'] ) ? intval( $response['tokens'] ) : 0;

        $data = json_decode( $content, true );
        if ( ! is_array( $data ) && preg_match( '/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $m ) ) {
            $data = json_decode( $m[1], true );
        }
        if ( is_array( $data ) ) {
            return [ 'data' => $data, 'tokens' => $tokens ];
        }

        Error_Logger::log( 'AI extraction parse error: ' . $content );
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
            'interest_paid',
            'annual_spending',
            'total_income',
            'annual_deficit',
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

    /**
     * Get total tokens used in the last extraction call.
     */
    public static function get_last_tokens() : int {
        return self::$last_tokens;
    }
}
