<?php

namespace App\Http\Middleware;

use App\Models\ShiftSchedule;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kasir hanya dapat bekerja sesuai jadwal shift dari admin atau pemilik toko.
 *
 * - Tanpa shift terbuka dan tanpa jadwal yang berlaku: sesi diakhiri.
 * - Shift terbuka tetapi jadwal sudah lewat (lebih dari toleransi): hanya
 *   halaman tutup shift yang dapat dibuka.
 */
class EnsureKasirSchedule
{
    /** Rute yang tetap dapat dibuka setelah jadwal lewat agar kasir bisa menutup shift. */
    private const OVERDUE_ROUTES = ['kasir.shift.edit', 'kasir.shift.close'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shift = $user->openShift()->with('schedule')->first();

        if ($shift) {
            if ($shift->schedule?->isOverdue() && ! $request->routeIs(...self::OVERDUE_ROUTES)) {
                $message = 'Jadwal shift Anda berakhir pukul '.$shift->schedule->waktu_selesai->format('H:i')
                    .'. Hitung uang di laci lalu tutup shift.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message, 'redirect' => route('kasir.shift.edit')], 409);
                }

                return redirect()->route('kasir.shift.edit')->with('warning', $message);
            }

            return $next($request);
        }

        // Ringkasan shift tetap dapat dilihat (dan dicetak) setelah shift ditutup.
        if ($request->routeIs('kasir.shift.show') || $user->currentSchedule()) {
            return $next($request);
        }

        $message = ShiftSchedule::unavailableMessage($user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'redirect' => route('login')], 409);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
