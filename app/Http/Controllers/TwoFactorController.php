<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            $user->forceFill(['two_factor_secret' => Totp::generateSecret()])->save();
            $user->refresh();
        }

        return view('two-factor.show', [
            'user' => $user,
            'secret' => $user->two_factor_secret,
            'otpauth' => Totp::otpauthUrl(
                config('app.name', 'Police Case Management'),
                $user->email,
                $user->two_factor_secret
            ),
            'enabled' => $user->hasTwoFactorEnabled(),
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That authentication code is invalid.']);
        }

        $codes = Totp::recoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $codes),
        ])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.two_factor_enabled',
            'table_name' => 'users',
            'record_id' => $user->id,
            'details' => 'Two-factor authentication enabled.',
        ]);

        return redirect()->route('two-factor.show')
            ->with('success', 'Two-factor authentication enabled.')
            ->with('two_factor_recovery_codes', $codes);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.two_factor_disabled',
            'table_name' => 'users',
            'record_id' => $user->id,
            'details' => 'Two-factor authentication disabled.',
        ]);

        return redirect()->route('two-factor.show')
            ->with('success', 'Two-factor authentication disabled.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->withErrors(['password' => 'Enable two-factor authentication first.']);
        }

        $codes = Totp::recoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $codes),
        ])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.two_factor_recovery_codes_regenerated',
            'table_name' => 'users',
            'record_id' => $user->id,
            'details' => 'Two-factor recovery codes regenerated.',
        ]);

        return redirect()->route('two-factor.show')
            ->with('success', 'Recovery codes regenerated.')
            ->with('two_factor_recovery_codes', $codes);
    }

    public function challenge(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::find($userId);

        if (! $user || ! $user->is_active || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('two_factor.user_id');

            return redirect()->route('login')->withErrors([
                'email' => 'Your sign-in session has expired. Please sign in again.',
            ]);
        }

        $code = trim((string) $request->input('code'));
        $valid = Totp::verify($user->two_factor_secret, $code);
        $usedRecoveryCode = false;

        if (! $valid) {
            $codes = $user->two_factor_recovery_codes ?? [];

            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);

                    $user->forceFill([
                        'two_factor_recovery_codes' => array_values($codes),
                    ])->save();

                    $valid = true;
                    $usedRecoveryCode = true;
                    break;
                }
            }
        }

        if (! $valid) {
            return back()->withErrors(['code' => 'That authentication code is invalid.']);
        }

        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget('two_factor.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $usedRecoveryCode
                ? 'auth.two_factor_recovery_code_used'
                : 'auth.two_factor_login',
            'table_name' => 'users',
            'record_id' => $user->id,
            'details' => $usedRecoveryCode
                ? 'Signed in using a two-factor recovery code.'
                : 'Signed in with two-factor authentication.',
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function cancel(Request $request)
    {
        $request->session()->forget(['two_factor.user_id', 'two_factor.remember']);

        return redirect()->route('login');
    }
}
