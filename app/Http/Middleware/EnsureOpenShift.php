<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kasir harus membuka shift (mencatat kas awal) sebelum bertransaksi.
 */
class EnsureOpenShift
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shift = $user->openShift()->first();

        if (! $shift) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Shift belum dibuka.'], 409);
            }

            return redirect()->route('kasir.shift.create')
                ->with('warning', 'Buka shift terlebih dahulu dengan mencatat kas awal.');
        }

        $user->setRelation('openShift', $shift);
        $request->attributes->set('shift', $shift);

        return $next($request);
    }
}
