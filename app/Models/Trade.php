<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = ['name'];

    public function personnelTrades()
    {
        return $this->belongsTo(\App\Models\Trade::class, 'trade_id');
    }
}