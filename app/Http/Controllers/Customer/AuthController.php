<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Support\TwoFactorAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    /**
     * Show customer login form
     */
    public function showLogin()
    {
        try {
            if (Auth::check()) {
                return redirect()->route('account.customer.dashboard');
            }
        } catch (Throwable $exception) {
            Log::warning('Customer auth check failed on login page.', [
                'message' => $exception->getMessage(),
                'ip_address' => request()->ip(),
            ]);
        }

        return view('customer.auth.login');
    }

    /**
     * Handle customer login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');

        try {
            if (Auth::attempt($credentials)) {
                /** @var User|null $user */
                $user = Auth::user();

                // Check if 2FA is enabled and confirmed
                if ($user && $user->two_factor_enabled && $user->two_factor_confirmed_at) {
                    // Store user ID in session and log them out until 2FA is verified
                    $userId = $user->id;
                    Auth::logout();
                    $request->session()->put('2fa_user_id', $userId);
                    return redirect()->route('account.customer.2fa.verify');
                }

                $request->session()->regenerate();

                if ($user) {
                    try {
                        $user->activities()->create([
                            'action' => 'logged_in',
                            'description' => 'User logged in',
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                    } catch (Throwable $exception) {
                        Log::warning('Failed to store customer login activity.', [
                            'message' => $exception->getMessage(),
                            'user_id' => $user->id,
                        ]);
                    }
                }

                return redirect()->route('account.customer.dashboard');
            }
        } catch (Throwable $exception) {
            Log::error('Customer login failed due to database issue.', [
                'message' => $exception->getMessage(),
                'ip_address' => $request->ip(),
                'email' => $credentials['email'] ?? null,
            ]);

            return back()->withErrors([
                'email' => 'Login is temporarily unavailable. Please try again shortly.',
            ])->withInput();
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
    }

    /**
     * Show 2FA verification form
     */
    public function showTwoFactor(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('account.customer.login');
        }

        return view('customer.auth.two-factor');
    }

    /**
     * Verify 2FA code during login
     */
    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $userId = $request->session()->get('2fa_user_id');
        if (!$userId) {
            return redirect()->route('account.customer.login')
                ->with('error', 'Session expired. Please log in again.');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('2fa_user_id');
            return redirect()->route('account.customer.login')
                ->with('error', 'Account not found.');
        }

        $code = $request->code;
        $verified = false;

        // Try TOTP code first
        if (strlen($code) === 6 && TwoFactorAuth::verify($user->two_factor_secret, $code)) {
            $verified = true;
        }

        // Try recovery code if TOTP failed
        if (!$verified) {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];
            if (!is_array($recoveryCodes)) {
                $recoveryCodes = json_decode($recoveryCodes, true) ?? [];
            }
            $codeUpper = strtoupper(trim($code));
            if (in_array($codeUpper, $recoveryCodes)) {
                $verified = true;
                // Remove used recovery code
                $recoveryCodes = array_values(array_diff($recoveryCodes, [$codeUpper]));
                $user->update(['two_factor_recovery_codes' => $recoveryCodes]);
            }
        }

        if (!$verified) {
            return back()->with('error', 'Invalid verification code.');
        }

        // 2FA verified — complete login
        $request->session()->forget('2fa_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->activities()->create([
                'action' => 'logged_in',
                'description' => 'User logged in (with 2FA)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to store customer login activity.', [
                'message' => $exception->getMessage(),
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('account.customer.dashboard');
    }

    /**
     * Show customer signup form
     */
    public function showSignup()
    {
        try {
            if (Auth::check()) {
                return redirect()->route('account.customer.dashboard');
            }
        } catch (Throwable $exception) {
            Log::warning('Customer auth check failed on signup page.', [
                'message' => $exception->getMessage(),
                'ip_address' => request()->ip(),
            ]);
        }

        return view('customer.auth.signup');
    }

    /**
     * Handle customer signup
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Auth::login($user);

        // Log activity
        $user->activities()->create([
            'action' => 'account_created',
            'description' => 'Account created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.customer.dashboard')->with('success', 'Welcome to Constraal!');
    }

    /**
     * Handle customer logout
     */
    public function logout(Request $request)
    {
        // Log activity before logout
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->activities()->create([
                'action' => 'logged_out',
                'description' => 'User logged out',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.customer.login')->with('success', 'You have been logged out');
    }

    /**
     * Show password reset form
     */
    public function showResetPassword()
    {
        return view('customer.auth.reset-password');
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Generate reset token
            $token = Str::random(64);
            DB::table('password_resets')->where('email', $user->email)->delete();
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            $resetUrl = url('/account/reset-password/confirm?token=' . $token . '&email=' . urlencode($user->email));

            try {
                Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name, 'customer'));
            } catch (\Exception $e) {
                Log::error('Customer password reset email failed: ' . $e->getMessage());
            }

            // Log activity
            $user->activities()->create([
                'action' => 'password_reset_requested',
                'description' => 'Password reset requested',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Always show success to prevent email enumeration
        return back()->with('success', 'If an account with that email exists, a password reset link has been sent.');
    }

    /**
     * Show new password form (from email link)
     */
    public function showNewPassword(Request $request)
    {
        return view('customer.auth.new-password', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle new password submission
     */
    public function setNewPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reset = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.'])->withInput();
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($reset->created_at) > 60) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'This reset link has expired. Please request a new one.'])->withInput();
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_resets')->where('email', $request->email)->delete();

        $user->activities()->create([
            'action' => 'password_reset_completed',
            'description' => 'Password was reset via email link',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.customer.login')->with('success', 'Password reset successfully. You can now log in with your new password.');
    }
}
