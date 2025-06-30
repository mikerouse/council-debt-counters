<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores whistleblower reports submitted by the public.
 */
class WhistleblowerReport extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'council_id',
        'description',
        'contact_email',
        'attachment_path',
        'status',
    ];

    public function council()
    {
        return $this->belongsTo(Council::class);
    }
}
