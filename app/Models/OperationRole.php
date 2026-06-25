<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationRole extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}