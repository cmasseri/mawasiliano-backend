<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelEducation extends Model
{
    protected $table = 'personnel_education';

    protected $fillable = [
        'personnel_id',
        'name',
        'institution',
    'year_completion'
    ];
}