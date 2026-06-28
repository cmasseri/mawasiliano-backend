<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PersonnelTraining;
class Personnel extends Model
{
    protected $table = 'personnel';

    protected $fillable = [
        'full_name',
        'service_number',
        'unit_id',
        'gender',
        'education_level',
        'date_of_birth',
        'status',
        'date_of_enlistment',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_enlistment' => 'date',
    ];

    // =============================
    // RELATIONS
    // =============================

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // 🔥 PROMOTIONS (RANK HISTORY)
  public function promotions()
{
    return $this->hasMany(Promotion::class)
                ->with('rank')
                ->orderByDesc('date_promoted');
}

    public function currentPromotion()
    {
        return $this->hasOne(Promotion::class)
            ->where('is_current', true);
    }

    // 🔥 TRADES
    public function trades()
    {
        return $this->hasMany(PersonnelTrade::class);
    }

    public function currentTrade()
    {
        return $this->hasOne(PersonnelTrade::class)
            ->where('is_current', true);
    }

    // 🔥 APPOINTMENTS
    public function appointments()
    {
        return $this->hasMany(PersonnelAppointment::class);
    }

    public function currentAppointment()
    {
        return $this->hasOne(PersonnelAppointment::class)
            ->where('is_current', true);
    }

    public function education()
{
    return $this->hasMany(
        PersonnelEducation::class,
        'personnel_id'
    );
}
public function trainings()
{
    return $this->hasMany(PersonnelTraining::class);
}

public function retirementExtensions()
{
    return $this->hasMany(RetirementExtension::class);
}


public function activeRetirementExtension()
{
    return $this->hasOne(RetirementExtension::class)
                ->where('is_active', true);
}
}