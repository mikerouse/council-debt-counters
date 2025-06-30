<?php

namespace App\Services;

use App\Models\Council;
use Illuminate\Support\Collection;

/**
 * Service handling total debt and income calculations for councils.
 * This mirrors the logic in the legacy WordPress plugin while
 * keeping the implementation testable and extendable.
 */
class CouncilTotalsService
{
    /**
     * Calculate the total debt for a council including any manual
     * adjustments recorded in the debt_adjustments field.
     */
    public function calculateDebt(Council $council): float
    {
        $adjust = collect($council->debt_adjustments)->sum('amount');

        return (float) $council->current_liabilities
            + (float) $council->long_term_liabilities
            + (float) $council->finance_lease_pfi_liabilities
            + (float) $council->manual_debt_entry
            + (float) $adjust;
    }

    /**
     * Calculate the total income for a council.
     */
    public function calculateIncome(Council $council): float
    {
        return (float) $council->non_council_tax_income
            + (float) $council->council_tax_general_grants_income
            + (float) $council->government_grants_income
            + (float) $council->all_other_income;
    }

    /**
     * Persist updated totals on the provided council record.
     */
    public function updateTotals(Council $council): Council
    {
        $council->total_debt = $this->calculateDebt($council);
        $council->total_income = $this->calculateIncome($council);
        $council->save();

        return $council;
    }
}
