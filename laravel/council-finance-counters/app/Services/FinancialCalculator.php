<?php

namespace App\Services;

use App\Models\Council;

/**
 * Helper methods that replicate the totals logic from the WordPress plugin.
 */
class FinancialCalculator
{
    /**
     * Calculate and update the total debt for a council for a given year.
     */
    public static function updateTotalDebt(Council $council): void
    {
        $total = (float) $council->current_liabilities
            + (float) $council->long_term_liabilities
            + (float) $council->finance_lease_pfi_liabilities
            + (float) $council->manual_debt_entry;

        $council->total_debt = $total;
        $council->save();
    }

    /**
     * Calculate and update the total income for a council for a given year.
     */
    public static function updateTotalIncome(Council $council): void
    {
        $total = (float) $council->non_council_tax_income
            + (float) $council->council_tax_general_grants_income
            + (float) $council->government_grants_income
            + (float) $council->all_other_income;

        $council->total_income = $total;
        $council->save();
    }
}
