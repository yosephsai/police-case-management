<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasePerson extends Model
{
    protected $table = 'case_people';

    protected $fillable = [
        'case_id',
        'full_name',
        'role',
        'phone',
        'notes',
    ];

    public function case()
    {
        return $this->belongsTo(PoliceCase::class, 'case_id');
    }
}