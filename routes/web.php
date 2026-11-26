<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseAssignmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\CasePersonController;
use App\Http\Controllers\InvestigationReportController;
use App\Http\Controllers\EvidenceHistoryController;
use App\Http\Controllers\InvestigationActivityController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PoliceCaseController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TwoFactorController;

use App\Models\User;

Route::get('/', [AuthController::class, 'showLogin'])
    ->name('login');

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.submit');

    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
            ->name('password.request');

        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:6,1')
            ->name('password.email');

        Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
            ->name('password.reset');

        Route::post('/reset-password', [PasswordResetController::class, 'reset'])
            ->name('password.update');
    });

    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])
        ->name('two-factor.challenge');

    Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.verify');

    Route::post('/two-factor/cancel', [TwoFactorController::class, 'cancel'])
        ->name('two-factor.cancel');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/{rolePath}/dashboard', [DashboardController::class, 'roleDashboard'])
        ->whereIn('rolePath', array_values(User::ROLE_SLUGS))
        ->name('role.dashboard');

    Route::prefix('admin')->group(function () {
        Route::middleware('can:manage-staff')->group(function () {
            Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggle'])
                ->name('staff.toggle');

            Route::resource('staff', StaffController::class)
                ->except(['show']);
        });

        Route::middleware('can:manage-permissions')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])
                ->name('permissions.index');

            Route::put('/permissions', [PermissionController::class, 'update'])
                ->name('permissions.update');

            Route::get('/staff/{staff}/permissions', [PermissionController::class, 'editUser'])
                ->name('staff.permissions.edit');

            Route::put('/staff/{staff}/permissions', [PermissionController::class, 'updateUser'])
                ->name('staff.permissions.update');
        });
    });

    Route::prefix('account')->group(function () {
        Route::get('/two-factor', [TwoFactorController::class, 'show'])
            ->name('two-factor.show');

        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])
            ->name('two-factor.confirm');

        Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])
            ->name('two-factor.disable');

        Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
            ->name('two-factor.recovery-codes');
    });

    Route::prefix('operations')->group(function () {
        Route::resource('complaints', ComplaintController::class)
            ->only(['create', 'store'])
            ->middleware('can:register-complaints');

        Route::resource('complaints', ComplaintController::class)
            ->only(['index', 'show']);

        Route::post('/complaints/{complaint}/review',
            [ComplaintController::class, 'review'])
            ->middleware('can:review-complaints')
            ->name('complaints.review');

        Route::post('/complaints/{complaint}/convert-to-case',
            [ComplaintController::class, 'convertToCase'])
            ->middleware('can:create-cases')
            ->name('complaints.convert-to-case');

        Route::post('/cases/{case}/assignments',
            [CaseAssignmentController::class, 'store'])
            ->middleware('can:assign-cases')
            ->name('case-assignments.store');

        Route::post('/cases/{case}/activities',
            [InvestigationActivityController::class, 'store'])
            ->middleware('can:record-activities')
            ->name('investigation-activities.store');

        Route::post('/cases/{case}/evidences',
            [EvidenceController::class, 'store'])
            ->middleware('can:add-evidence')
            ->name('evidences.store');

        Route::get('/evidences/{evidence}/attachment',
            [EvidenceController::class, 'download'])
            ->name('evidences.attachment');

        Route::post('/evidences/{evidence}/histories',
            [EvidenceHistoryController::class, 'store'])
            ->middleware('can:handle-evidence')
            ->name('evidence-histories.store');

        Route::post('/cases/{case}/people',
            [CasePersonController::class, 'store'])
            ->middleware('can:manage-people')
            ->name('case-people.store');

        Route::post('/cases/{case}/reports',
            [InvestigationReportController::class, 'store'])
            ->middleware('can:submit-report')
            ->name('investigation-reports.store');

        Route::post('/investigation-reports/{report}/review',
            [InvestigationReportController::class, 'review'])
            ->middleware('can:review-report')
            ->name('investigation-reports.review');

        Route::resource('cases', PoliceCaseController::class)
            ->only(['index', 'show']);

        Route::patch('/cases/{case}/status',
            [PoliceCaseController::class, 'updateStatus'])
            ->middleware('can:update-case-status')
            ->name('cases.status');

        Route::get('/cases/{case}/print',
            [PoliceCaseController::class, 'printView'])
            ->name('cases.print');
    });

    Route::prefix('oversight')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('can:view-audit-logs')
            ->name('audit-logs.index');

        Route::middleware('can:view-reports')->group(function () {
            Route::get('/reports/complaints.csv',
                [ReportExportController::class, 'complaints'])
                ->name('reports.complaints');

            Route::get('/reports/cases.csv',
                [ReportExportController::class, 'cases'])
                ->name('reports.cases');

            Route::get('/reports/summary/print',
                [ReportExportController::class, 'summary'])
                ->name('reports.summary');
        });
    });
});
