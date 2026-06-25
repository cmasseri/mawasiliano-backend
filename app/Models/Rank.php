<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $fillable = ['name'];

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }
}