<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $fillable = ['name','type','location','country','start_date','end_date','status','description'];

    public function personnel()
    {
        return $this->hasMany(PersonnelOperation::class);
    }
}