<?php

namespace App\Models;
use App\Models\Personnel;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'personnel_id',
        'from_unit_id',
        'to_unit_id',
        'transfer_date',
        'reason'
    ];

    // =============================
    // PERSONNEL
    // =============================

    public function personnel()
    {
        return $this->belongsTo(
            Personnel::class,
            'personnel_id'
        );
    }

    // =============================
    // FROM UNIT
    // =============================

    public function fromUnit()
    {
        return $this->belongsTo(
            Unit::class,
            'from_unit_id'
        );
    }

    // =============================
    // TO UNIT
    // =============================

    public function toUnit()
    {
        return $this->belongsTo(
            Unit::class,
            'to_unit_id'
        );
    }
}