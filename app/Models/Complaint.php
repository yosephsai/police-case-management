<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';

    protected $fillable = [
        'complaint_number',
        'complainant_name',
        'complainant_phone',
        'incident_location',
        'incident_description',
        'incident_date',
        'status',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_by' => 'integer',
        ];
    }

    public function case()
    {
        return $this->hasOne(PoliceCase::class, 'complaint_id');
    }

    public function reviews()
    {
        return $this->hasMany(ComplaintReview::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasPermission('view-all-complaints')) {
            return $query;
        }

        if ($user->hasPermission('view-assigned-complaints')) {
            return $query->where(function ($query) use ($user) {
                $query->where('registered_by', $user->id)
                    ->orWhereHas('case.assignments', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            });
        }

        return $query->where('registered_by', $user->id);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->hasPermission('view-all-complaints')) {
            return true;
        }

        if ($this->registered_by === $user->id) {
            return true;
        }

        if ($user->hasPermission('view-assigned-complaints') && $this->case) {
            return $this->case->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }
}
