<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetirementExtension extends Model
{
    protected $fillable = [
        'personnel_id',
        'approval_date',
        'effective_from',
        'effective_to',
        'extension_years',
        'approval_reference',
        'reason',
        'remarks',
        'is_active'
    ];

    protected $casts = [
        'approval_date'  => 'date',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean'
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
}