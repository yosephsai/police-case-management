<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\EvidenceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EvidenceHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request, Evidence $evidence)
    {
        Gate::authorize('work-on-case', $evidence->case);

        $validated = $request->validate([
            'action' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'handled_at' => ['required', 'date'],
        ]);

        $history = EvidenceHistory::create([
            'evidence_id' => $evidence->id,
            'user_id' => auth()->id(),
            'action' => $validated['action'],
            'notes' => $validated['notes'] ?? null,
            'handled_at' => $validated['handled_at'],
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'evidence.handling_recorded',
            'table_name' => 'evidence_histories',
            'record_id' => $history->id,
            'details' => 'Evidence handling recorded for evidence ' . $evidence->evidence_number,
        ]);

        return back()->with('success', 'Evidence handling recorded successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
