<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
class PersonnelAppointment extends Model
{
    protected $fillable = [
        'personnel_id',
        'appointment_id',
        'start_date',
        'end_date',
        'is_current'
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function appointment()
    {
  return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}