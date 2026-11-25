<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InvestigationActivity;
use App\Models\PoliceCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvestigationActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request, PoliceCase $case)
    {
        Gate::authorize('work-on-case', $case);

        $validated = $request->validate([
            'activity_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'activity_date' => ['required', 'date'],
        ]);

        $activity = InvestigationActivity::create([
            'case_id' => $case->id,
            'user_id' => auth()->id(),
            'activity_type' => $validated['activity_type'],
            'description' => $validated['description'],
            'activity_date' => $validated['activity_date'],
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'investigation_activity.created',
            'table_name' => 'investigation_activities',
            'record_id' => $activity->id,
            'details' => 'Investigation activity recorded for case ' . $case->case_number,
        ]);

        return back()->with('success', 'Investigation activity recorded successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

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
