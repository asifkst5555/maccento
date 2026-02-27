<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthOtpController extends Controller
{
    public function showLogin(Request $request): View
    {
        return view('login', [
            'otpRequested' => (bool) session('otp_requested', false),
            'identifier' => (string) session('otp_identifier', $request->query('identifier', '')),
            'channel' => (string) session('otp_channel', 'auto'),
        ]);
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'in:auto,email,sms'],
        ]);

        $identifier = $this->normalizeIdentifier($validated['identifier']);
        $channel = $validated['channel'] ?? 'auto';
        $resolvedChannel = $this->resolveChannel($identifier, $channel);

        if ($resolvedChannel === 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['identifier' => 'Please enter a valid email address.'])->withInput();
        }

        if ($resolvedChannel === 'sms') {
            $normalizedPhone = $this->normalizePhone($identifier);
            if ($normalizedPhone === null) {
                return back()->withErrors(['identifier' => 'Please enter a valid phone number.'])->withInput();
            }
            $identifier = $normalizedPhone;
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->otpCacheKey($identifier);

        Cache::put($cacheKey, [
            'identifier' => $identifier,
            'channel' => $resolvedChannel,
            'otp' => $otp,
            'attempts' => 0,
        ], now()->addMinutes(10));

        if ($resolvedChannel === 'email') {
            try {
                Mail::raw("Your Maccento verification code is: {$otp}. It expires in 10 minutes.", function ($message) use ($identifier): void {
                    $message->to($identifier)->subject('Maccento Login Verification Code');
                });
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP email', ['identifier' => $identifier, 'error' => $e->getMessage()]);
                return back()->withErrors(['identifier' => 'Unable to send OTP email right now. Please try again.'])->withInput();
            }
        } else {
            // Hook SMS provider here (Twilio/Vonage/etc). For now we log OTP for development.
            Log::info('SMS OTP generated', ['phone' => $identifier, 'otp' => $otp]);
        }

        $redirect = redirect()
            ->route('login', ['identifier' => $identifier])
            ->with('otp_requested', true)
            ->with('otp_identifier', $identifier)
            ->with('otp_channel', $resolvedChannel)
            ->with('status', 'OTP sent successfully.');

        if (config('app.debug')) {
            $redirect->with('otp_debug_code', $otp);
        }

        return $redirect;
    }

    public function showRegister(): View
    {
        return view('register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
        ]);

        $phone = null;
        if (!empty($validated['phone'])) {
            $phone = $this->normalizePhone($validated['phone']);
            if ($phone === null) {
                return back()->withErrors(['phone' => 'Please enter a valid phone number.'])->withInput();
            }
        }

        User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => $phone,
            'password' => Str::random(40),
        ]);

        return redirect()
            ->route('login', ['identifier' => Str::lower(trim($validated['email']))])
            ->with('status', 'Account created. Enter your email to receive OTP.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);

        $identifier = $this->normalizeIdentifier($validated['identifier']);
        $cacheKey = $this->otpCacheKey($identifier);
        $payload = Cache::get($cacheKey);

        if (!$payload || !isset($payload['otp'])) {
            return back()->withErrors(['otp' => 'OTP expired or not found. Please request a new code.'])->withInput();
        }

        if ($payload['otp'] !== $validated['otp']) {
            $payload['attempts'] = (int) ($payload['attempts'] ?? 0) + 1;
            if ($payload['attempts'] >= 5) {
                Cache::forget($cacheKey);
                return back()->withErrors(['otp' => 'Too many attempts. Please request a new OTP.'])->withInput();
            }

            Cache::put($cacheKey, $payload, now()->addMinutes(10));
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.'])->withInput();
        }

        Cache::forget($cacheKey);

        $channel = $payload['channel'] ?? 'email';
        $user = $channel === 'sms'
            ? $this->firstOrCreateByPhone($identifier)
            : $this->firstOrCreateByEmail($identifier);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function resolveChannel(string $identifier, string $channel): string
    {
        if ($channel === 'email' || $channel === 'sms') {
            return $channel;
        }

        return filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return trim(Str::lower($identifier));
    }

    private function normalizePhone(string $phone): ?string
    {
        $candidate = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if ($candidate === '') {
            return null;
        }

        if (!str_starts_with($candidate, '+')) {
            $candidate = '+' . ltrim($candidate, '+');
        }

        $digits = preg_replace('/\D/', '', $candidate) ?? '';
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return '+' . $digits;
    }

    private function otpCacheKey(string $identifier): string
    {
        return 'auth_otp_' . sha1($identifier);
    }

    private function firstOrCreateByEmail(string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => Str::title(Str::before($email, '@')) ?: 'Client',
                'password' => Str::random(40),
            ]
        );
    }

    private function firstOrCreateByPhone(string $phone): User
    {
        $safe = preg_replace('/\D/', '', $phone) ?? Str::random(10);
        $email = "phone_{$safe}@maccento.local";

        return User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Client ' . substr($safe, -4),
                'email' => $email,
                'password' => Str::random(40),
            ]
        );
    }
}
