<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';

                $query->where(function ($query) use ($search) {
                    $query->where('action', 'like', $search)
                        ->orWhere('details', 'like', $search);
                });
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->filled('table_name'), function ($query) use ($request) {
                $query->where('table_name', $request->input('table_name'));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'tables' => AuditLog::query()->whereNotNull('table_name')->distinct()->orderBy('table_name')->pluck('table_name'),
        ]);
    }
}
