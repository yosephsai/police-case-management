<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\PoliceCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class EvidenceController extends Controller
{
    public function download(Request $request, Evidence $evidence)
    {
        Gate::authorize('view-case', $evidence->case);

        abort_unless(
            $evidence->attachment_path && Storage::disk('local')->exists($evidence->attachment_path),
            404
        );

        $extension = strtolower(pathinfo($evidence->attachment_path, PATHINFO_EXTENSION));
        if ($request->boolean('inline') && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return Storage::disk('local')->response($evidence->attachment_path);
        }

        return Storage::disk('local')->download($evidence->attachment_path);
    }

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
            'evidence_number' => ['required', 'string', 'max:255', 'unique:evidences,evidence_number'],
            'evidence_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'collected_at' => ['required', 'date', 'before_or_equal:today'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,txt'],
        ], [
            'collected_at.before_or_equal' => 'The collection date cannot be in the future.',
            'attachment.mimes' => 'Supported attachment formats: PDF, JPG, JPEG, PNG, WEBP, GIF, DOC, DOCX, or TXT.',
            'attachment.max' => 'The attachment may not be larger than 10 MB.',
            'attachment.uploaded' => 'The attachment could not be uploaded. Check that the file is within the server upload limit.',
        ]);

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('evidence', 'local')
            : null;

        $evidence = Evidence::create([
            'case_id' => $case->id,
            'user_id' => auth()->id(),
            'evidence_number' => $validated['evidence_number'],
            'evidence_type' => $validated['evidence_type'],
            'description' => $validated['description'],
            'storage_location' => $validated['storage_location'] ?? null,
            'collected_at' => $validated['collected_at'],
            'attachment_path' => $attachmentPath,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'evidence.created',
            'table_name' => 'evidences',
            'record_id' => $evidence->id,
            'details' => 'Evidence recorded for case ' . $case->case_number,
        ]);

        return redirect(route('cases.show', $case) . '#section-evidence')
            ->with('success', 'Evidence recorded successfully.');
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
