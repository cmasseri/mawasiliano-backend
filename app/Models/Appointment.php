<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [

        'name',

        'unit_id'

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Unit ambayo appointment ipo.
     */
    public function unit()
    {
        return $this->belongsTo(
            Unit::class,
            'unit_id'
        );
    }

    /**
     * Personnel appointments zote zilizowahi kutumia appointment hii.
     */
    public function personnelAppointments()
    {
        return $this->hasMany(
            PersonnelAppointment::class,
            'appointment_id'
        );
    }

    /**
     * Personnel wote waliopo kwenye appointment hii kwa sasa.
     */
    public function currentPersonnel()
    {
        return $this->hasMany(
            PersonnelAppointment::class,
            'appointment_id'
        )->where('is_current', true);
    }
}