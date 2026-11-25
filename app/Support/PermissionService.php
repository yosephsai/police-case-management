<?php

namespace App\Support;

use App\Models\PermissionOverride;
use App\Models\User;
use App\Models\UserPermissionOverride;

class PermissionService
{
    private ?array $roleOverrides = null;

    private ?array $userOverrides = null;

    public function allowsFor(User $user, string $permission): bool
    {
        $userKey = $user->id . '|' . $permission;
        $userOverrides = $this->userOverrides();

        if (array_key_exists($userKey, $userOverrides)) {
            return (bool) $userOverrides[$userKey];
        }

        $roleKey = $user->role . '|' . $permission;
        $roleOverrides = $this->roleOverrides();

        if (array_key_exists($roleKey, $roleOverrides)) {
            return (bool) $roleOverrides[$roleKey];
        }

        return in_array($user->role, config('permissions.permissions.' . $permission, []), true);
    }

    public function roleOverrides(): array
    {
        if ($this->roleOverrides === null) {
            $this->roleOverrides = PermissionOverride::query()
                ->get()
                ->mapWithKeys(function (PermissionOverride $override) {
                    return [
                        $override->role . '|' . $override->permission => $override->allowed,
                    ];
                })
                ->all();
        }

        return $this->roleOverrides;
    }

    public function userOverrides(): array
    {
        if ($this->userOverrides === null) {
            $this->userOverrides = UserPermissionOverride::query()
                ->get()
                ->mapWithKeys(function (UserPermissionOverride $override) {
                    return [
                        $override->user_id . '|' . $override->permission => $override->allowed,
                    ];
                })
                ->all();
        }

        return $this->userOverrides;
    }

    public function flush(): void
    {
        $this->roleOverrides = null;
        $this->userOverrides = null;
    }
}
