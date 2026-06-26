<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetirementRule extends Model
{
    protected $fillable = [
        'rank_id',
        'retirement_age'
    ];

    public $timestamps = false;

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}