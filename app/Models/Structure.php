<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $table = 'units';

    protected $fillable = [
        'name',
        'type',
        'nickname',
        'parent_id',
        'child_type'
    ];
}