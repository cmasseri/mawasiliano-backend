<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rank; 

class Promotion extends Model
{
    protected $fillable = [
        'personnel_id',
        'rank_id',
        'date_promoted',
        'is_current'
    ];

    protected $casts = [
        'date_promoted' => 'date',
        'is_current' => 'boolean'
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

 
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }

    
}