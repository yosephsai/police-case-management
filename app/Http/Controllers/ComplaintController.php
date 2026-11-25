<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseStatusHistory;
use App\Models\Complaint;
use App\Models\ComplaintReview;
use App\Models\PoliceCase;
use App\Support\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);

        $search = trim($filters['search'] ?? '');
        $like = '%' . $search . '%';
        $phrase = $search !== '' ? SearchIndex::phrase($search) : null;
        $useFts = $phrase !== null && SearchIndex::available('complaints_fts');

        $complaints = Complaint::query()
            ->visibleTo($request->user())
            ->when($search !== '', function ($query) use ($like, $phrase, $useFts) {
                if ($useFts) {
                    $query->join('complaints_fts', 'complaints_fts.rowid', '=', 'complaints.id')
                        ->whereRaw('complaints_fts MATCH ?', [$phrase])
                        ->select('complaints.*');
                } else {
                    $query->where(function ($query) use ($like) {
                        $query->where('complaint_number', 'like', $like)
                            ->orWhere('complainant_name', 'like', $like)
                            ->orWhere('incident_location', 'like', $like);
                    });
                }
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('incident_date', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('incident_date', '<=', $filters['to']))
            ->when($search !== '', function ($query) use ($search, $like, $useFts) {
                $query->orderByRaw(
                    'CASE WHEN complaints.complaint_number = ? THEN 0 WHEN complaints.complaint_number LIKE ? THEN 1 WHEN complaints.complainant_name LIKE ? THEN 2 ELSE 3 END',
                    [$search, $search . '%', $like]
                );

                if ($useFts) {
                    $query->orderByRaw('bm25(complaints_fts)');
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('complaints.index', [
            'complaints' => $complaints,
            'statuses' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
        ]);
    }

    public function create()
    {
        return view('complaints.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^(?:\+251)?[0-9]{9}$/',
            ],
            'incident_location' => ['required', 'string', 'max:255'],
            'incident_description' => ['required', 'string'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'complainant_phone.regex' => 'Enter a 9-digit phone number, or the full +251 number.',
            'incident_date.before_or_equal' => 'The incident date cannot be in the future.',
        ]);

        if (! empty($validated['complainant_phone'])) {
            $phone = $validated['complainant_phone'];
            $validated['complainant_phone'] = str_starts_with($phone, '+251')
                ? $phone
                : '+251' . $phone;
        }

        $validated['complaint_number'] =
            'CMP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $validated['status'] = 'pending';
        $validated['registered_by'] = auth()->id();

        $complaint = Complaint::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'complaint.created',
            'table_name' => 'complaints',
            'record_id' => $complaint->id,
            'details' => 'Complaint registered with number ' . $complaint->complaint_number,
        ]);

        return redirect()->route('complaints.index')
            ->with('success', 'Complaint registered successfully.');
    }

    public function show(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->isVisibleTo($request->user()), 403);

        $complaint->load(['reviews.user', 'case', 'registeredBy']);

        return view('complaints.show', compact('complaint'));
    }

    public function review(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->isVisibleTo($request->user()), 403);

        if ($complaint->status !== 'pending') {
            return back()->withErrors([
                'review' => 'This complaint has already been reviewed.',
            ]);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string'],
        ]);

        DB::transaction(function () use ($complaint, $validated) {
            $complaint->update([
                'status' => $validated['decision'],
            ]);

            ComplaintReview::create([
                'complaint_id' => $complaint->id,
                'user_id' => auth()->id(),
                'decision' => $validated['decision'],
                'reason' => $validated['reason'] ?? null,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'complaint.review',
                'table_name' => 'complaints',
                'record_id' => $complaint->id,
                'details' => 'Complaint review decision: ' . $validated['decision'],
            ]);
        });

         return redirect()->route('complaints.show', $complaint)
            ->with('success', 'Complaint review saved successfully.');
    }

    public function convertToCase(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->isVisibleTo($request->user()), 403);

        if ($complaint->status !== 'approved') {
            return back()->withErrors([
                'case' => 'Only approved complaints can be converted into cases.',
            ]);
        }

        if (PoliceCase::where('complaint_id', $complaint->id)->exists()) {
            return back()->withErrors([
                'case' => 'A case has already been created for this complaint.',
            ]);
        }

        $case = DB::transaction(function () use ($complaint) {
            $case = PoliceCase::create([
                'complaint_id' => $complaint->id,
                'case_number' => 'CASE-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'title' => 'Case from complaint ' . $complaint->complaint_number,
                'description' => $complaint->incident_description,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'complaint.convert_to_case',
                'table_name' => 'cases',
                'record_id' => $case->id,
                'details' => 'Case created from complaint ' . $complaint->complaint_number,
            ]);

            CaseStatusHistory::create([
                'case_id' => $case->id,
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => 'open',
                'notes' => 'Case created from complaint ' . $complaint->complaint_number,
                'changed_at' => now(),
            ]);

            return $case;
        });

        return redirect()->route('cases.show', $case)
            ->with('success', 'Complaint successfully converted into a case.');
    }
}
