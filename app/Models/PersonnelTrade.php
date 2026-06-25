<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelTrade extends Model
{
    protected $fillable = [
        'personnel_id',
        'trade_id',
        'start_date',
        'end_date',
        'is_current'
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function trade()
    {
         return $this->belongsTo(Trade::class, 'trade_id');
    }
}