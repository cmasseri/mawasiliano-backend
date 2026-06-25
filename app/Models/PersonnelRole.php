<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelRole extends Model
{
    protected $fillable = ['personnel_id','role_id','unit_id','start_date','end_date'];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}