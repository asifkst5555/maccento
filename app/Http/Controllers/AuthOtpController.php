<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class AuthOtpController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $remember = (bool) ($validated['remember'] ?? false);

        try {
            $user = User::where('email', $email)->first();

            if ($user) {
                $status = strtolower(trim((string) $user->status));
                if ($status !== 'active') {
                    $errorMessage = match ($status) {
                        'suspended' => 'Your account has been suspended. Please contact admin support.',
                        'locked' => 'Your account has been locked due to security constraints. Please contact support.',
                        'archived' => 'This account is archived and cannot be accessed.',
                        'pending_verification' => 'Please verify your credentials before logging in.',
                        default => 'Your account is currently inactive.',
                    };

                    // Log failed login: blocked by status
                    $uaData = \App\Models\LoginRecord::parseUserAgent($request->userAgent());
                    \App\Models\LoginRecord::create(array_merge($uaData, [
                        'user_id' => $user->id,
                        'email' => $email,
                        'login_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'is_success' => false,
                        'failure_reason' => 'Account status blocked: ' . $status,
                    ]));

                    return back()
                        ->withErrors(['email' => $errorMessage])
                        ->withInput($request->only('email'));
                }

                // Attempt authentication
                if (Auth::attempt(['email' => $email, 'password' => $validated['password']], $remember)) {
                    $request->session()->regenerate();
                    $user->update(['last_login_at' => now()]);

                    // Log successful login
                    $uaData = \App\Models\LoginRecord::parseUserAgent($request->userAgent());
                    \App\Models\LoginRecord::create(array_merge($uaData, [
                        'user_id' => $user->id,
                        'email' => $email,
                        'login_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'is_success' => true,
                    ]));

                    $normalizedRole = strtolower(trim((string) $user->role));
                    $internalRoles = ['admin', 'owner', 'manager', 'photographer', 'editor', 'super_admin'];

                    return redirect()->route(in_array($normalizedRole, $internalRoles, true) ? 'admin.dashboard' : 'user.dashboard');
                } else {
                    // Log failed login: wrong password
                    $uaData = \App\Models\LoginRecord::parseUserAgent($request->userAgent());
                    \App\Models\LoginRecord::create(array_merge($uaData, [
                        'user_id' => $user->id,
                        'email' => $email,
                        'login_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'is_success' => false,
                        'failure_reason' => 'Invalid credentials',
                    ]));

                    return back()
                        ->withErrors(['email' => 'Invalid email or password.'])
                        ->withInput($request->only('email'));
                }
            } else {
                // Log failed login: email not found
                $uaData = \App\Models\LoginRecord::parseUserAgent($request->userAgent());
                \App\Models\LoginRecord::create(array_merge($uaData, [
                    'user_id' => null,
                    'email' => $email,
                    'login_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'is_success' => false,
                    'failure_reason' => 'Account not found',
                ]));

                if (User::query()->doesntExist()) {
                    return redirect()
                        ->route('signup')
                        ->with('status', 'No user account exists yet. Create your first account to continue.');
                }

                return back()
                    ->withErrors(['email' => 'Invalid email or password.'])
                    ->withInput($request->only('email'));
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => 'Login is temporarily unavailable. Please try again in a moment.'])
                ->withInput($request->only('email'));
        }
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
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $phone = null;
        if (!empty($validated['phone'])) {
            $phone = $this->normalizePhone($validated['phone']);
            if ($phone === null) {
                return back()->withErrors(['phone' => 'Please enter a valid phone number.'])->withInput();
            }
        }

        $isFirstAdmin = User::whereIn('role', ['admin', 'super_admin'])->doesntExist();
        $roleName = $isFirstAdmin ? 'super_admin' : 'client';
        $roleModel = \App\Models\Role::where('name', $roleName)->first();
        $targetRoleString = $isFirstAdmin ? 'admin' : 'client';

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => $phone,
            'role' => $targetRoleString,
            'role_id' => $roleModel?->id,
            'status' => 'active',
            'password' => $validated['password'],
            'last_login_at' => now(),
        ]);

        if ($user->role === 'client') {
            \App\Models\Client::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => 'active',
                'notes' => 'Created automatically from public website registration.',
            ]);
        }

        Auth::login($user);

        // Log successful login from registration
        $uaData = \App\Models\LoginRecord::parseUserAgent($request->userAgent());
        \App\Models\LoginRecord::create(array_merge($uaData, [
            'user_id' => $user->id,
            'email' => $user->email,
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'is_success' => true,
        ]));

        $normalizedRole = strtolower(trim((string) $user->role));
        $internalRoles = ['admin', 'owner', 'manager', 'photographer', 'editor', 'super_admin'];

        return redirect()->route(in_array($normalizedRole, $internalRoles, true) ? 'admin.dashboard' : 'user.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $latestRecord = \App\Models\LoginRecord::where('user_id', $user->id)
                ->where('is_success', true)
                ->whereNull('logout_at')
                ->latest('id')
                ->first();
            if ($latestRecord) {
                $latestRecord->update(['logout_at' => now()]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
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
}
