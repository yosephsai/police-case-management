<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAssignment extends Model
{
    protected $table = 'case_assignments';

    protected $fillable = [
        'case_id',
        'user_id',
        'role',
        'assigned_at',
    ];

    public function case()
    {
        return $this->belongsTo(PoliceCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}