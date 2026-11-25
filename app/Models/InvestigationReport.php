<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestigationReport extends Model
{
    protected $fillable = [
        'case_id',
        'submitted_by',
        'report_summary',
        'submitted_at',
        'review_status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    public function case()
    {
        return $this->belongsTo(PoliceCase::class, 'case_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
