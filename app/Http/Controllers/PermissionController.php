<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Support\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return view('permissions.index', [
            'roles' => config('permissions.roles'),
            'permissionMap' => config('permissions.permissions'),
            'labels' => config('permissions.labels'),
            'overrides' => PermissionOverride::query()
                ->get()
                ->keyBy(fn (PermissionOverride $override) => $override->role . '|' . $override->permission),
        ]);
    }

    public function update(Request $request, PermissionService $permissions)
    {
        $roles = array_keys(config('permissions.roles'));
        $permissionMap = config('permissions.permissions');
        $input = $request->input('permissions', []);

        foreach ($roles as $role) {
            foreach (array_keys($permissionMap) as $permission) {
                if ($permission === 'manage-permissions') {
                    continue;
                }

                $checked = ! empty($input[$role][$permission]);
                $default = in_array($role, $permissionMap[$permission], true);

                if ($checked === $default) {
                    PermissionOverride::where('role', $role)
                        ->where('permission', $permission)
                        ->delete();
                } else {
                    PermissionOverride::updateOrCreate(
                        ['role' => $role, 'permission' => $permission],
                        ['allowed' => $checked]
                    );
                }
            }
        }

        $permissions->flush();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'permissions.updated',
            'table_name' => 'permission_overrides',
            'record_id' => null,
            'details' => 'Role permission overrides updated by administrator.',
        ]);

        return back()->with('success', 'Permission settings updated successfully.');
    }

    public function editUser(User $staff, PermissionService $permissions)
    {
        $permissionMap = config('permissions.permissions');

        $effective = [];
        foreach (array_keys($permissionMap) as $permission) {
            $effective[$permission] = $permissions->allowsFor($staff, $permission);
        }

        return view('permissions.user', [
            'staff' => $staff,
            'permissionMap' => $permissionMap,
            'labels' => config('permissions.labels'),
            'overrides' => UserPermissionOverride::query()
                ->where('user_id', $staff->id)
                ->pluck('allowed', 'permission'),
            'effective' => $effective,
        ]);
    }

    public function updateUser(Request $request, User $staff, PermissionService $permissions)
    {
        $permissionMap = config('permissions.permissions');
        $input = $request->input('overrides', []);

        foreach (array_keys($permissionMap) as $permission) {
            if ($permission === 'manage-permissions') {
                continue;
            }

            $value = $input[$permission] ?? '';

            if ($value === '' || $value === null) {
                UserPermissionOverride::where('user_id', $staff->id)
                    ->where('permission', $permission)
                    ->delete();
            } else {
                UserPermissionOverride::updateOrCreate(
                    ['user_id' => $staff->id, 'permission' => $permission],
                    ['allowed' => (bool) $value]
                );
            }
        }

        $permissions->flush();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'user_permissions.updated',
            'table_name' => 'user_permission_overrides',
            'record_id' => $staff->id,
            'details' => 'Permission overrides updated for ' . $staff->name . ' (' . $staff->email . ').',
        ]);

        return back()->with('success', 'User permissions updated successfully.');
    }
}
