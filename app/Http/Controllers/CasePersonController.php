<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CasePerson;
use App\Models\PoliceCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CasePersonController extends Controller
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
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $person = CasePerson::create([
            'case_id' => $case->id,
            'full_name' => $validated['full_name'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'case_person.created',
            'table_name' => 'case_people',
            'record_id' => $person->id,
            'details' => 'Person associated with case ' . $case->case_number,
        ]);

        return back()->with('success', 'Person associated with case successfully.');
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
