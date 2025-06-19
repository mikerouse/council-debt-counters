<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Extractor {
    /**
     * Ask OpenAI to extract key figures from a statement of accounts text.
     *
     * @param string $text Raw text from the statement of accounts.
     * @return mixed WP_Error on failure or array of extracted values.
     */
    public static function extract_key_figures( string $text ) {
        $prompt = "You are analysing a UK council's statement of accounts. "
            . "Return ONLY a JSON object with these keys: current_liabilities, "
            . "long_term_liabilities, finance_lease_pfi_liabilities, "
            . "interest_paid_on_debt and minimum_revenue_provision. "
            . "Numbers should be plain digits without currency symbols or commas."
            . "\n" . $text;
        $response = OpenAI_Helper::query( $prompt );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $data = json_decode( $response, true );
        if ( is_array( $data ) ) {
            return $data;
        }
        return new \WP_Error( 'invalid_ai_response', __( 'Failed to parse AI response.', 'council-debt-counters' ) );
    }
}
