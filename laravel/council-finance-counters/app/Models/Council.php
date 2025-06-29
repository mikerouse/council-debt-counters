<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Basic Council model storing key finance fields.
 */

class Council extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'population',
        'households',
        'current_liabilities',
        'long_term_liabilities',
        'finance_lease_pfi_liabilities',
        'manual_debt_entry',
        'non_council_tax_income',
        'council_tax_general_grants_income',
        'government_grants_income',
        'all_other_income',
        'total_debt',
        'total_income',
    ];
}
