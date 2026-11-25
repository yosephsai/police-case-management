<?php

namespace App\Providers;

use App\Models\PoliceCase;
use App\Models\User;
use App\Support\PermissionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(6)->by($request->session()->getId());
        });

        foreach (array_keys(config('permissions.permissions', [])) as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        Gate::define('view-case', function (User $user, PoliceCase $case) {
            if ($user->hasPermission('view-all-cases')) {
                return true;
            }

            return $case->assignments()->where('user_id', $user->id)->exists();
        });

        Gate::define('work-on-case', function (User $user, PoliceCase $case) {
            if ($user->hasPermission('view-all-cases')) {
                return true;
            }

            return $case->assignments()->where('user_id', $user->id)->exists();
        });
    }
}
