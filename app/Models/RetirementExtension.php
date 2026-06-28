<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetirementExtension extends Model
{
    protected $fillable = [

        'personnel_id',

        'years_extended',

        'approval_date',

        'approved_by',

        'reference_number',

        'reason',

        'remarks',

        'is_active'

    ];

    protected $casts = [

        'approval_date' => 'date',

        'is_active' => 'boolean'

    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }
}