<?php

namespace App\Services;

use App\Models\Council;
use App\Services\CouncilTotalsService;

/**
 * Legacy helper methods kept for backwards compatibility.
 * Internally this class proxies to CouncilTotalsService.
 */
class FinancialCalculator
{
    protected static CouncilTotalsService $service;

    public function __construct(CouncilTotalsService $service)
    {
        self::$service = $service;
    }

    /**
     * Calculate and update the total debt for a council for a given year.
     */
    public static function updateTotalDebt(Council $council): void
    {
        self::getService()->updateTotals($council);
    }

    /**
     * Calculate and update the total income for a council for a given year.
     */
    public static function updateTotalIncome(Council $council): void
    {
        self::getService()->updateTotals($council);
    }

    protected static function getService(): CouncilTotalsService
    {
        if (!isset(self::$service)) {
            self::$service = new CouncilTotalsService();
        }
        return self::$service;
    }
}
