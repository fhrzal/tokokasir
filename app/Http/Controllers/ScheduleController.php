<?php

namespace App\Http\Controllers;

use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Jadwal shift kasir, dikelola admin dan pemilik toko.
 */
class ScheduleController extends Controller
{
    /** Batas rentang tanggal sekali buat. */
    private const MAX_DAYS = 62;

    public function index(Request $request): View
    {
        $week = $this->weekStart($request->query('minggu'));
        $weekEnd = $week->addDays(7);

        $schedules = ShiftSchedule::with(['user', 'shift'])
            ->where('waktu_mulai', '>=', $week)
            ->where('waktu_mulai', '<', $weekEnd)
            ->orderBy('waktu_mulai')
            ->get()
            ->groupBy(fn (ShiftSchedule $s) => $s->waktu_mulai->toDateString());

        return view('schedules.index', [
            'week' => $week,
            'days' => collect(range(0, 6))->map(fn (int $i) => $week->addDays($i)),
            'schedules' => $schedules,
            'cashiers' => $this->cashiers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', $this->cashierRule()],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'hari' => ['required_with:tanggal_selesai', 'array'],
            'hari.*' => ['integer', 'between:1,7'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'different:jam_mulai'],
            'catatan' => ['nullable', 'string', 'max:100'],
        ], [
            'user_id.required' => 'Pilih kasir.',
            'hari.required_with' => 'Pilih minimal satu hari.',
            'jam_selesai.different' => 'Jam selesai harus berbeda dengan jam mulai.',
        ]);

        $from = CarbonImmutable::createFromFormat('Y-m-d', $data['tanggal_mulai'])->startOfDay();
        $to = filled($data['tanggal_selesai'] ?? null)
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['tanggal_selesai'])->startOfDay()
            : $from;

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            throw ValidationException::withMessages(['tanggal_selesai' => 'Rentang tanggal maksimal '.self::MAX_DAYS.' hari.']);
        }

        // Tanpa rentang tanggal, pilihan hari diabaikan.
        $days = $from->eq($to) ? range(1, 7) : array_map('intval', $data['hari'] ?? []);

        $slots = collect(range(0, (int) $from->diffInDays($to)))
            ->map(fn (int $i) => $from->addDays($i))
            ->filter(fn (CarbonImmutable $date) => in_array($date->dayOfWeekIso, $days, true))
            ->map(fn (CarbonImmutable $date) => ShiftSchedule::span($date, $data['jam_mulai'], $data['jam_selesai']))
            ->reject(fn (array $span) => $span[1]->lte(now()))
            ->values();

        if ($slots->isEmpty()) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Tidak ada jadwal yang dapat dibuat: waktunya sudah lewat atau tidak ada hari yang cocok.']);
        }

        $kasir = User::findOrFail($data['user_id']);

        DB::transaction(function () use ($kasir, $slots, $data, $request) {
            // Kunci baris kasir agar pemeriksaan bentrok tidak didahului permintaan lain.
            $kasir->newQuery()->whereKey($kasir->getKey())->lockForUpdate()->first();

            $this->ensureNoOverlap($kasir, $slots->all());

            foreach ($slots as [$start, $end]) {
                $kasir->schedules()->create([
                    'waktu_mulai' => $start,
                    'waktu_selesai' => $end,
                    'catatan' => $data['catatan'] ?? null,
                    'dibuat_oleh' => $request->user()->user_id,
                ]);
            }
        });

        return redirect()->route('jadwal.index', ['minggu' => $slots[0][0]->toDateString()])
            ->with('success', $slots->count().' jadwal untuk '.$kasir->nama.' berhasil dibuat.');
    }

    public function edit(ShiftSchedule $schedule): View|RedirectResponse
    {
        if (! $schedule->isEditable()) {
            return $this->locked($schedule);
        }

        return view('schedules.edit', [
            'schedule' => $schedule->load(['user', 'shift']),
            'cashiers' => $this->cashiers(),
        ]);
    }

    public function update(Request $request, ShiftSchedule $schedule): RedirectResponse
    {
        if (! $schedule->isEditable()) {
            return $this->locked($schedule);
        }

        $started = (bool) $schedule->shift;

        $data = $request->validate([
            'user_id' => [$started ? 'prohibited' : 'required', $this->cashierRule()],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'different:jam_mulai'],
            'catatan' => ['nullable', 'string', 'max:100'],
        ], [
            'jam_selesai.different' => 'Jam selesai harus berbeda dengan jam mulai.',
        ]);

        [$start, $end] = ShiftSchedule::span(
            CarbonImmutable::createFromFormat('Y-m-d', $data['tanggal']), $data['jam_mulai'], $data['jam_selesai'],
        );

        // Jadwal yang sedang berjalan boleh dipersingkat (mis. kasir pulang lebih awal).
        if (! $started && $end->lte(now())) {
            throw ValidationException::withMessages(['jam_selesai' => 'Jadwal sudah lewat. Pilih waktu yang akan datang.']);
        }

        $kasir = $started ? $schedule->user : User::findOrFail($data['user_id']);

        DB::transaction(function () use ($kasir, $schedule, $start, $end, $data) {
            $kasir->newQuery()->whereKey($kasir->getKey())->lockForUpdate()->first();

            $this->ensureNoOverlap($kasir, [[$start, $end]], $schedule);

            $schedule->update([
                'user_id' => $kasir->user_id,
                'waktu_mulai' => $start,
                'waktu_selesai' => $end,
                'catatan' => $data['catatan'] ?? null,
            ]);
        });

        return redirect()->route('jadwal.index', ['minggu' => $start->toDateString()])
            ->with('success', 'Jadwal '.$kasir->nama.' diperbarui.');
    }

    public function destroy(ShiftSchedule $schedule): RedirectResponse
    {
        if (! $schedule->isDeletable()) {
            return back()->with('error', $schedule->shift
                ? 'Jadwal yang sudah dimulai tidak dapat dihapus.'
                : 'Jadwal yang sudah lewat disimpan sebagai riwayat dan tidak dapat dihapus.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal '.$schedule->user->nama.' '.$schedule->longLabel().' dihapus.');
    }

    /**
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $slots
     */
    private function ensureNoOverlap(User $kasir, array $slots, ?ShiftSchedule $ignore = null): void
    {
        $conflicts = collect($slots)
            ->map(fn (array $span) => ShiftSchedule::overlapping($kasir->user_id, ...$span)
                ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
                ->first())
            ->filter()
            ->unique('schedule_id');

        if ($conflicts->isNotEmpty()) {
            $list = $conflicts->take(3)
                ->map(fn (ShiftSchedule $s) => $s->waktu_mulai->translatedFormat('D, j M').' '.$s->timeLabel())
                ->join('; ');
            $more = $conflicts->count() > 3 ? ' dan '.($conflicts->count() - 3).' lainnya' : '';

            throw ValidationException::withMessages([
                'jam_mulai' => "Bentrok dengan jadwal {$kasir->nama} yang sudah ada: {$list}{$more}.",
            ]);
        }
    }

    private function locked(ShiftSchedule $schedule): RedirectResponse
    {
        return redirect()->route('jadwal.index', ['minggu' => $schedule->waktu_mulai->toDateString()])
            ->with('error', 'Jadwal yang sudah selesai disimpan sebagai riwayat dan tidak dapat diubah.');
    }

    private function weekStart(?string $date): CarbonImmutable
    {
        try {
            $day = $date ? CarbonImmutable::createFromFormat('Y-m-d', $date) : CarbonImmutable::today();
        } catch (\Throwable) {
            $day = CarbonImmutable::today();
        }

        return $day->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
    }

    private function cashiers()
    {
        return User::where('role', User::ROLE_KASIR)->where('status', true)->orderBy('nama')->get(['user_id', 'nama']);
    }

    private function cashierRule()
    {
        return Rule::exists('users', 'user_id')->where('role', User::ROLE_KASIR)->where('status', true);
    }
}
