<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliceCase extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'complaint_id',
        'case_number',
        'title',
        'description',
        'status',
        'opened_at',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function assignments()
    {
        return $this->hasMany(CaseAssignment::class, 'case_id');
    }

    public function investigators()
    {
        return $this->belongsToMany(
            User::class,
            'case_assignments',
            'case_id',
            'user_id'
        )->withPivot('role', 'assigned_at')->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(InvestigationActivity::class, 'case_id');
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'case_id');
    }

    public function people()
    {
        return $this->hasMany(CasePerson::class, 'case_id');
    }

    public function reports()
    {
        return $this->hasMany(InvestigationReport::class, 'case_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(CaseStatusHistory::class, 'case_id')->latest('changed_at');
    }

    public function leadInvestigator()
    {
        return $this->assignments()->where('role', 'lead')->first()?->user;
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasPermission('view-all-cases')) {
            return $query;
        }

        return $query->whereHas('assignments', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }
}