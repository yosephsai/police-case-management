<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::orderBy('id')->get();

        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create', ['roles' => User::roleOptions()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
        ]);

        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'staff.created',
            'table_name' => 'users',
            'record_id' => $member->id,
            'details' => 'Staff account created for ' . $member->name . ' with role ' . $member->role,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account created successfully.');
    }

    public function edit(User $staff)
    {
        return view('staff.edit', [
            'staff' => $staff,
            'roles' => User::roleOptions(),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $staff->id],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'password' => ['nullable', 'min:8'],
        ]);

        if ($this->isLastActiveAdmin($staff) && $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'The last active administrator cannot be given a different role.',
            ]);
        }

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->role = $validated['role'];

        if (! empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $staff->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'staff.updated',
            'table_name' => 'users',
            'record_id' => $staff->id,
            'details' => 'Staff account updated for ' . $staff->name . ' (role ' . $staff->role . ')',
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account updated successfully.');
    }

    public function toggle(User $staff)
    {
        if ($staff->id === auth()->id()) {
            return back()->withErrors([
                'staff' => 'You cannot deactivate your own account.',
            ]);
        }

        if ($this->isLastActiveAdmin($staff)) {
            return back()->withErrors([
                'staff' => 'The last active administrator cannot be deactivated.',
            ]);
        }

        $staff->is_active = ! $staff->is_active;
        $staff->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $staff->is_active ? 'staff.activated' : 'staff.deactivated',
            'table_name' => 'users',
            'record_id' => $staff->id,
            'details' => 'Staff account ' . ($staff->is_active ? 'activated' : 'deactivated') . ': ' . $staff->name,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account status updated successfully.');
    }

    public function destroy(User $staff)
    {
        if ($staff->id === auth()->id()) {
            return back()->withErrors([
                'staff' => 'You cannot delete your own account.',
            ]);
        }

        if ($this->isLastActiveAdmin($staff)) {
            return back()->withErrors([
                'staff' => 'The last active administrator cannot be deleted.',
            ]);
        }

        $name = $staff->name;

        $staff->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'staff.deleted',
            'table_name' => 'users',
            'record_id' => null,
            'details' => 'Staff account deleted: ' . $name,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account deleted successfully.');
    }

    private function isLastActiveAdmin(User $staff): bool
    {
        if ($staff->role !== 'admin' || ! $staff->is_active) {
            return false;
        }

        return User::where('role', 'admin')->where('is_active', true)->count() <= 1;
    }
}
