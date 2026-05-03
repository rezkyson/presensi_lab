<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = $this->findAuthenticatableUser($credentials['identifier']);

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'identifier' => 'NIM/email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        if (! $request->user()?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'identifier' => 'Akun ini sedang nonaktif. Hubungi admin.',
            ]);
        }

        return redirect()->intended($this->dashboardPathFor($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function findAuthenticatableUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        return Mahasiswa::query()
            ->where('nim', $identifier)
            ->with('user')
            ->first()
            ?->user;
    }

    private function dashboardPathFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => '/admin/dashboard',
            User::ROLE_DOSEN => '/dosen/dashboard',
            User::ROLE_MAHASISWA => '/mahasiswa/dashboard',
            default => '/',
        };
    }
}
