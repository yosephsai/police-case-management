<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestigationActivity extends Model
{
    protected $fillable = [
        'case_id',
        'user_id',
        'activity_type',
        'description',
        'activity_date',
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