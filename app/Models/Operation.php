<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $fillable = [
        'name',
        'type',
        'location',
        'country',
        'start_date',
        'end_date',
        'description'
    ];

    protected $casts = [
    'start_date' => 'date:d-M-Y',
    'end_date' => 'date:d-M-Y',

];

    protected $appends = ['status'];

    public function personnel()
    {
        return $this->hasMany(PersonnelOperation::class);
    }

public function getStatusAttribute(): string
{
    $today = today();

    if ($today->lt($this->start_date)) {
        return 'PLANNED';
    }

    if (
        $today->between(
            $this->start_date,
            $this->end_date
        )
    ) {
        return 'ONGOING';
    }

    return 'COMPLETED';
}
}