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
        $schedule = $request->user()->currentSchedule();

        return view('kasir.shift-open', compact('lastShift', 'schedule'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kas_awal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $user = $request->user();

        $opened = DB::transaction(function () use ($user, $data) {
            // Kunci baris pengguna agar tidak terbuka dua shift sekaligus.
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->first();

            if ($user->openShift()->exists()) {
                return true;
            }

            // Shift hanya dapat dibuka dalam jadwal yang ditetapkan admin/pemilik.
            $schedule = $user->currentSchedule();
            if (! $schedule) {
                return false;
            }

            $user->shifts()->create([
                'schedule_id' => $schedule->schedule_id,
                'kas_awal' => $data['kas_awal'],
                'waktu_buka' => now(),
            ]);

            return true;
        });

        if (! $opened) {
            return redirect()->route('kasir.shift.create')
                ->with('error', 'Shift tidak dapat dibuka karena tidak ada jadwal yang berlaku saat ini.');
        }

        return redirect()->route('kasir.pos')->with('success', 'Shift dibuka. Selamat bertugas!');
    }

    public function edit(Request $request): View
    {
        $shift = $request->attributes->get('shift');

        return view('kasir.shift-close', [
            'shift' => $shift,
            'summary' => $shift->summary(),
            'earlyCloseMessage' => $shift->canBeClosed() ? null : self::earlyCloseMessage($shift),
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

        if (! $shift->canBeClosed()) {
            return back()->withInput()->with('error', self::earlyCloseMessage($shift));
        }

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
            'canOpenShift' => ! $request->user()->openShift()->exists() && $request->user()->currentSchedule(),
            'nextSchedule' => $request->user()->schedules()->upcoming()->first(),
        ]);
    }

    private static function earlyCloseMessage(Shift $shift): string
    {
        return 'Shift baru dapat ditutup mulai pukul '.$shift->schedule->closableAt()->format('H:i')
            .' sesuai jadwal '.$shift->schedule->timeLabel().'. Bila perlu pulang lebih awal, '
            .'minta admin atau pemilik toko mengubah jam selesai jadwal Anda.';
    }
}
