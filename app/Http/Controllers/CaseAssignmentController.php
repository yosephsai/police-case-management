<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseAssignment;
use App\Models\PoliceCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseAssignmentController extends Controller
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
        Gate::authorize('view-case', $case);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'in:lead,investigator'],
        ]);

        $assignee = \App\Models\User::find($validated['user_id']);

        if (! $assignee || ! $assignee->is_active || ! in_array($assignee->role, ['lead_detective', 'investigator'], true)) {
            return back()->withErrors([
                'user_id' => 'Only active lead detectives or assistant investigators can be assigned to a case.',
            ]);
        }

        $assignmentRole = $assignee->role === 'lead_detective' ? 'lead' : 'investigator';

        if (CaseAssignment::where('case_id', $case->id)
            ->where('user_id', $validated['user_id'])
            ->exists()) {
            return back()->withErrors([
                'user_id' => 'This investigator has already been assigned to this case.',
            ]);
        }

        if ($assignmentRole === 'lead'
            && CaseAssignment::where('case_id', $case->id)->where('role', 'lead')->exists()) {
            return back()->withErrors([
                'role' => 'This case already has a lead investigator.',
            ]);
        }

        $assignment = CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $validated['user_id'],
            'role' => $assignmentRole,
            'assigned_at' => now()->toDateString(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'case_assignment.created',
            'table_name' => 'case_assignments',
            'record_id' => $assignment->id,
            'details' => $assignee->name . ' assigned as ' . $assignmentRole . ' to case ' . $case->case_number,
        ]);

        return back()->with('success', 'Investigator assigned to case successfully.');
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
