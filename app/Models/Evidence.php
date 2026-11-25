<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $fillable = [
        'case_id',
        'user_id',
        'evidence_number',
        'evidence_type',
        'description',
        'storage_location',
        'collected_at',
        'attachment_path',
    ];

    public function case()
    {
        return $this->belongsTo(PoliceCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function histories()
    {
        return $this->hasMany(EvidenceHistory::class);
    }
}