<?php

namespace App\Http\Controllers;

use App\Models\Council;
use Illuminate\Http\Request;

class CouncilController extends Controller
{
    /**
     * Return aggregated totals across all councils.
     */
    public function totals()
    {
        $totalDebt = Council::sum('total_debt');
        return ['totalDebt' => (float) $totalDebt];
    }

    /**
     * Search councils by name.
     */
    public function search(Request $request)
    {
        $q = $request->input('q');
        if (! $q) {
            return [];
        }

        return Council::where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'slug']);
    }
}
