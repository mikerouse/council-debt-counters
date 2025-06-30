<?php

namespace Tests\Unit;

use App\Models\Council;
use App\Services\CouncilTotalsService;
use PHPUnit\Framework\TestCase;

class CouncilTotalsServiceTest extends TestCase
{
    public function test_calculates_totals_with_adjustments(): void
    {
        $council = new Council([
            'current_liabilities' => 100,
            'long_term_liabilities' => 200,
            'finance_lease_pfi_liabilities' => 50,
            'manual_debt_entry' => 25,
            'debt_adjustments' => [
                ['amount' => 10],
                ['amount' => -5],
            ],
            'non_council_tax_income' => 1000,
            'council_tax_general_grants_income' => 500,
            'government_grants_income' => 200,
            'all_other_income' => 300,
        ]);

        $service = new CouncilTotalsService();

        $this->assertEquals(380, $service->calculateDebt($council));
        $this->assertEquals(2000, $service->calculateIncome($council));
    }
}
