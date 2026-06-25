<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelTraining extends Model
{
        public $timestamps = false;
    protected $fillable = ['personnel_id','training_id', 'military_school', 'start_date','end_date'];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

public function training()
{
    return $this->belongsTo(
        Training::class,
        'training_id'
    );
}
}



