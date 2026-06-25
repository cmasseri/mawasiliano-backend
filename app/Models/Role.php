<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name','description'];

    public function personnelRoles()
    {
        return $this->hasMany(PersonnelRole::class);
    }
}