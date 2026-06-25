<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['name', 'unit_id'];

    public function personnelAppointments()
    {
        return $this->hasMany(PersonnelAppointment::class);
    }

    public function unit()
{
    return $this->belongsTo(
        Unit::class,
        'unit_id'
    );
}
}