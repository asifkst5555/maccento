<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

        if (User::query()->count() === 0) {
            return redirect()
                ->route('signup')
                ->with('status', 'No user account exists yet. Create your first account to continue.');
        }

        $email = Str::lower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput($request->only('email'));
        }

        Auth::login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        $normalizedRole = strtolower(trim((string) $user->role));
        $internalRoles = ['admin', 'owner', 'manager', 'photographer', 'editor'];

        return redirect()->route(in_array($normalizedRole, $internalRoles, true) ? 'admin.dashboard' : 'user.dashboard');
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
