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
            if ( isset( $data['annual_spending'], $data['total_income'] ) && ! isset( $data['annual_deficit'] ) ) {
                $data['annual_deficit'] = floatval( $data['annual_spending'] ) - floatval( $data['total_income'] );
            }
            return $data;
        }
        Error_Logger::log( 'AI extraction parse error: ' . $response );
        return new \WP_Error( 'invalid_ai_response', __( 'Failed to parse AI response.', 'council-debt-counters' ) );
    }
}
