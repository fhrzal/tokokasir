<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! Auth::user()->status) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi admin toko.',
            ]);
        }

        // Kasir hanya dapat masuk sesuai jadwal shift, kecuali masih ada shift
        // terbuka yang harus ditutup.
        $user = Auth::user();
        if ($user->hasRole(User::ROLE_KASIR) && ! $user->openShift()->exists() && ! $user->currentSchedule()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ShiftSchedule::unavailableMessage($user),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($user->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole(User::ROLE_KASIR) && $user->openShift()->exists()) {
            return redirect()->route('kasir.shift.edit')
                ->with('warning', 'Tutup shift terlebih dahulu sebelum keluar.');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
