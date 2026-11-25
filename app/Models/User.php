<?php

namespace App\Models;

use App\Support\PermissionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SLUGS = [
        'admin' => 'admin',
        'station_commander' => 'commander',
        'reporting_officer' => 'reporting',
        'reviewing_officer' => 'review',
        'supervisor' => 'supervisor',
        'lead_detective' => 'detective',
        'investigator' => 'investigator',
        'records_officer' => 'records',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function caseAssignments()
    {
        return $this->hasMany(CaseAssignment::class);
    }

    public function investigationActivities()
    {
        return $this->hasMany(InvestigationActivity::class);
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class);
    }

    public function evidenceHistories()
    {
        return $this->hasMany(EvidenceHistory::class);
    }

    public function complaintReviews()
    {
        return $this->hasMany(ComplaintReview::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasPermission(string $permission): bool
    {
        return app(PermissionService::class)->allowsFor($this, $permission);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function hasCaseAccess(): bool
    {
        return $this->hasPermission('view-all-cases') || $this->caseAssignments()->exists();
    }

    public function roleLabel(): string
    {
        return config('permissions.roles.' . $this->role, ucfirst((string) $this->role));
    }

    public function roleSlug(): string
    {
        return self::ROLE_SLUGS[$this->role] ?? str_replace('_', '-', (string) $this->role);
    }

    public static function roleOptions(): array
    {
        return config('permissions.roles', []);
    }
}