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
            if (Auth::attempt(['email' => $email, 'password' => $validated['password']], $remember)) {
                $request->session()->regenerate();
                $user = Auth::user();

                if ($user === null) {
                    return back()
                        ->withErrors(['email' => 'Invalid email or password.'])
                        ->withInput($request->only('email'));
                }

                $normalizedRole = strtolower(trim((string) $user->role));
                $internalRoles = ['admin', 'owner', 'manager', 'photographer', 'editor'];

                return redirect()->route(in_array($normalizedRole, $internalRoles, true) ? 'admin.dashboard' : 'user.dashboard');
            }

            if (User::query()->doesntExist()) {
                return redirect()
                    ->route('signup')
                    ->with('status', 'No user account exists yet. Create your first account to continue.');
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => 'Login is temporarily unavailable. Please try again in a moment.'])
                ->withInput($request->only('email'));
        }

        return back()
            ->withErrors(['email' => 'Invalid email or password.'])
            ->withInput($request->only('email'));
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

        $isFirstAdmin = User::where('role', 'admin')->doesntExist();

        User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => $phone,
            'role' => $isFirstAdmin ? 'admin' : 'user',
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('login')
            ->with('status', $isFirstAdmin
                ? 'Admin account created successfully. Please sign in.'
                : 'Account created successfully. Please sign in.');
    }

    public function logout(Request $request): RedirectResponse
    {
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
