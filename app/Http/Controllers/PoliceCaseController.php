<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseStatusHistory;
use App\Models\PoliceCase;
use App\Models\User;
use App\Support\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PoliceCaseController extends Controller
{
    public const STATUSES = [
        'open' => 'Open',
        'under_investigation' => 'Under Investigation',
        'pending_review' => 'Pending Review',
        'closed' => 'Closed',
    ];

    private const TRANSITIONS = [
        'open' => ['under_investigation'],
        'under_investigation' => ['pending_review'],
        'pending_review' => ['under_investigation', 'closed'],
        'closed' => [],
    ];

    public static function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);

        $search = trim($filters['search'] ?? '');
        $like = '%' . $search . '%';
        $phrase = $search !== '' ? SearchIndex::phrase($search) : null;
        $useFts = $phrase !== null && SearchIndex::available('cases_fts');

        $cases = PoliceCase::query()
            ->visibleTo(auth()->user())
            ->with('complaint')
            ->withCount(['assignments', 'activities', 'evidences', 'people', 'reports'])
            ->when($search !== '', function ($query) use ($like, $phrase, $useFts) {
                if ($useFts) {
                    $query->join('cases_fts', 'cases_fts.rowid', '=', 'cases.id')
                        ->whereRaw('cases_fts MATCH ?', [$phrase])
                        ->select('cases.*');
                } else {
                    $query->where(function ($query) use ($like) {
                        $query->where('case_number', 'like', $like)
                            ->orWhere('title', 'like', $like);
                    });
                }
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('opened_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('opened_at', '<=', $filters['to']))
            ->when($search !== '', function ($query) use ($search, $like, $useFts) {
                $query->orderByRaw(
                    'CASE WHEN cases.case_number = ? THEN 0 WHEN cases.case_number LIKE ? THEN 1 WHEN cases.title LIKE ? THEN 2 ELSE 3 END',
                    [$search, $search . '%', $like]
                );

                if ($useFts) {
                    $query->orderByRaw('bm25(cases_fts)');
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('cases.index', [
            'cases' => $cases,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(PoliceCase $case)
    {
        Gate::authorize('view-case', $case);

        $case->load([
            'complaint',
            'assignments.user',
            'activities.user',
            'evidences.user',
            'evidences.histories.user',
            'people',
            'reports.submittedBy',
            'reports.reviewedBy',
            'statusHistories.user',
        ]);

        $investigators = User::whereIn('role', ['lead_detective', 'investigator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('cases.show', [
            'case' => $case,
            'investigators' => $investigators,
            'statuses' => self::STATUSES,
            'allowedTransitions' => self::allowedTransitions($case->status),
        ]);
    }

    public function printView(PoliceCase $case)
    {
        Gate::authorize('view-case', $case);

        $case->load([
            'complaint',
            'assignments.user',
            'activities.user',
            'evidences.user',
            'evidences.histories.user',
            'people',
            'reports.submittedBy',
            'reports.reviewedBy',
            'statusHistories.user',
        ]);

        return view('cases.print', [
            'case' => $case,
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, PoliceCase $case)
    {
        Gate::authorize('view-case', $case);

        $validated = $request->validate([
            'to_status' => ['required', 'string', Rule::in(array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);

        $allowed = self::allowedTransitions($case->status);

        if (! in_array($validated['to_status'], $allowed, true)) {
            return back()->withErrors([
                'to_status' => 'That status change is not permitted from the current case status.',
            ]);
        }

        $fromStatus = $case->status;

        DB::transaction(function () use ($case, $fromStatus, $validated) {
            $case->update(['status' => $validated['to_status']]);

            CaseStatusHistory::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => $validated['to_status'],
                'notes' => $validated['notes'] ?? null,
                'changed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'case.status_changed',
                'table_name' => 'cases',
                'record_id' => $case->id,
                'details' => 'Case status changed from ' . $fromStatus . ' to ' . $validated['to_status'],
            ]);
        });

        return back()->with('success', 'Case status updated successfully.');
    }
}
