<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelEducation extends Model
{
    protected $table = 'personnel_education';

    protected $fillable = [

        'personnel_id',

        'qualification',

        'field_of_study',

        'institution',

        'year_completion'

    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function qualification()
    {
        return $this->belongsTo(
            Qualification::class,
            'qualification_id'
        );
    }
}