<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactorAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    /**
     * Display security settings
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $loginActivity = $user->activities()
            ->where('action', 'logged_in')
            ->latest()
            ->take(10)
            ->get();

        return view('customer.security.index', [
            'user' => $user,
            'loginActivity' => $loginActivity,
            'twoFactorEnabled' => $user->two_factor_enabled && $user->two_factor_confirmed_at,
        ]);
    }

    /**
     * Show change password form
     */
    public function showChangePassword()
    {
        return view('customer.security.change-password');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        $user->activities()->create([
            'action' => 'password_changed',
            'description' => 'Password changed from security page',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Password updated successfully');
    }

    /**
     * Show two-factor setup page with QR code
     */
    public function showTwoFactorSetup(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Generate a new secret
        $secret = TwoFactorAuth::generateSecret();
        $qrUri = TwoFactorAuth::getQrUri($secret, $user->email);
        $recoveryCodes = TwoFactorAuth::generateRecoveryCodes();

        // Store temporarily in session until user confirms
        $request->session()->put('2fa_setup_secret', $secret);
        $request->session()->put('2fa_setup_recovery_codes', $recoveryCodes);

        return view('customer.security.two-factor-setup', [
            'secret' => $secret,
            'qrUri' => $qrUri,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Enable Two-Factor Authentication after code verification
     */
    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $secret = $request->session()->get('2fa_setup_secret');
        $recoveryCodes = $request->session()->get('2fa_setup_recovery_codes');

        if (!$secret) {
            return redirect()->route('account.customer.security.2fa-setup')
                ->with('error', 'Setup session expired. Please try again.');
        }

        // Verify the code against the secret
        if (!TwoFactorAuth::verify($secret, $request->code)) {
            return back()->with('error', 'Invalid verification code. Please try again.');
        }

        // Save 2FA settings
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->forget(['2fa_setup_secret', '2fa_setup_recovery_codes']);

        $user->activities()->create([
            'action' => 'two_factor_enabled',
            'description' => 'Two-factor authentication enabled',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.customer.security.index')
            ->with('success', 'Two-factor authentication has been enabled.');
    }

    /**
     * Disable Two-Factor Authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $user->activities()->create([
            'action' => 'two_factor_disabled',
            'description' => 'Two-factor authentication disabled',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Log out other sessions
     */
    public function logoutOtherSessions(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Delete all sessions for this user except the current one
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // Regenerate session token for added security
        $request->session()->regenerate();

        $user->activities()->create([
            'action' => 'other_sessions_logout',
            'description' => 'All other sessions logged out',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'All other sessions have been logged out');
    }
}
