<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelOperation extends Model
{
    protected $fillable = [
        'personnel_id','operation_id','role','unit_id',
        'start_date','end_date','status','remarks'
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }
}