<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceHistory extends Model
{
    protected $fillable = [
        'evidence_id',
        'user_id',
        'action',
        'notes',
        'handled_at',
    ];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}