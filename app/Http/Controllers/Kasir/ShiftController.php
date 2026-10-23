<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * UC-05 Kas Awal & Kas Akhir (Shift).
 */
class ShiftController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->openShift()->exists()) {
            return redirect()->route('kasir.pos');
        }

        $lastShift = $request->user()->shifts()->latest('waktu_buka')->first();

        return view('kasir.shift-open', compact('lastShift'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kas_awal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            // Kunci baris pengguna agar tidak terbuka dua shift sekaligus.
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->first();

            if (! $user->openShift()->exists()) {
                $user->shifts()->create([
                    'kas_awal' => $data['kas_awal'],
                    'waktu_buka' => now(),
                ]);
            }
        });

        return redirect()->route('kasir.pos')->with('success', 'Shift dibuka. Selamat bertugas!');
    }

    public function edit(Request $request): View
    {
        $shift = $request->attributes->get('shift');

        return view('kasir.shift-close', [
            'shift' => $shift,
            'summary' => $shift->summary(),
        ]);
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kas_akhir' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var Shift $shift */
        $shift = $request->attributes->get('shift');
        $summary = $shift->summary();

        $shift->update([
            'kas_akhir' => $data['kas_akhir'],
            'kas_seharusnya' => $summary['kas_seharusnya'],
            'selisih' => round($data['kas_akhir'] - $summary['kas_seharusnya'], 2),
            'catatan' => $data['catatan'] ?? null,
            'waktu_tutup' => now(),
        ]);

        return redirect()->route('kasir.shift.show', $shift)->with('success', 'Shift berhasil ditutup.');
    }

    public function show(Request $request, Shift $shift): View
    {
        abort_unless($shift->user_id === $request->user()->user_id, 403);

        return view('kasir.shift-summary', [
            'shift' => $shift,
            'summary' => $shift->summary(),
        ]);
    }
}
