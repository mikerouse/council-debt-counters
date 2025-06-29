<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores submitted figures for moderation before applying to a council.
 */
class FigureSubmission extends Model
{
    protected $fillable = [
        'council_id',
        'figures',
        'financial_year',
        'status',
    ];

    protected $casts = [
        'figures' => 'array',
    ];
}
