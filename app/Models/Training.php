<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = ['name','category'];

    public function personnel()
    {
        return $this->hasMany(PersonnelTraining::class);
    }

    public function personnelTrainings()
{
    return $this->hasMany(
        PersonnelTraining::class,
        'id'
    );
}
}