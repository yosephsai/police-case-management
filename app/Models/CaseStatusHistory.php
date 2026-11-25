<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStatusHistory extends Model
{
    protected $fillable = [
        'case_id',
        'user_id',
        'from_status',
        'to_status',
        'notes',
        'changed_at',
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
