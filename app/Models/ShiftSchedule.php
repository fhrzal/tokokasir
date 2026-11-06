<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Jadwal shift kasir yang dibuat admin atau pemilik toko.
 *
 * Aturan waktu (T = toleransi pada config tokokasir.jadwal_toleransi):
 * - masuk & buka shift   : waktu_mulai − T  s.d. waktu_selesai
 * - transaksi            : s.d. waktu_selesai + T
 * - tutup shift          : mulai waktu_selesai − T
 *
 * @property CarbonImmutable $waktu_mulai
 * @property CarbonImmutable $waktu_selesai
 */
class ShiftSchedule extends Model
{
    public const STATUS = [
        'terjadwal' => ['Terjadwal', 'badge-gray'],
        'menunggu' => ['Belum buka shift', 'badge-yellow'],
        'bertugas' => ['Sedang bertugas', 'badge-blue'],
        'lewat' => ['Belum tutup shift', 'badge-red'],
        'hadir' => ['Hadir', 'badge-green'],
        'tidak_hadir' => ['Tidak hadir', 'badge-red'],
    ];

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'user_id',
        'waktu_mulai',
        'waktu_selesai',
        'catatan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'immutable_datetime',
            'waktu_selesai' => 'immutable_datetime',
        ];
    }

    public static function tolerance(): int
    {
        return max(0, (int) config('tokokasir.jadwal_toleransi'));
    }

    /**
     * Rentang waktu dari tanggal + jam. Jam selesai yang lebih kecil atau sama
     * dengan jam mulai berarti shift berakhir keesokan harinya.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function span(CarbonInterface $date, string $jamMulai, string $jamSelesai): array
    {
        $day = CarbonImmutable::instance($date)->startOfDay();
        $start = $day->setTimeFromTimeString($jamMulai);
        $end = $day->setTimeFromTimeString($jamSelesai);

        return [$start, $end <= $start ? $end->addDay() : $end];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function shift(): HasOne
    {
        return $this->hasOne(Shift::class, 'schedule_id');
    }

    /** Jadwal yang sedang membuka akses masuk dan belum diselesaikan dengan tutup shift. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('waktu_mulai', '<=', now()->addMinutes(self::tolerance()))
            ->where('waktu_selesai', '>', now())
            ->whereDoesntHave('shift', fn (Builder $q) => $q->whereNotNull('waktu_tutup'))
            ->orderBy('waktu_mulai');
    }

    /** Jadwal yang akses masuknya belum dibuka. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('waktu_mulai', '>', now()->addMinutes(self::tolerance()))
            ->orderBy('waktu_mulai');
    }

    public function scopeOverlapping(Builder $query, int $userId, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where('waktu_mulai', '<', $end)
            ->where('waktu_selesai', '>', $start);
    }

    /** Paling cepat kasir boleh masuk dan membuka shift. */
    public function opensAt(): CarbonImmutable
    {
        return $this->waktu_mulai->subMinutes(self::tolerance());
    }

    /** Paling cepat shift boleh ditutup. */
    public function closableAt(): CarbonImmutable
    {
        return $this->waktu_selesai->subMinutes(self::tolerance());
    }

    /** Batas akhir transaksi; setelah ini kasir hanya dapat menutup shift. */
    public function salesEndAt(): CarbonImmutable
    {
        return $this->waktu_selesai->addMinutes(self::tolerance());
    }

    public function isOverdue(): bool
    {
        return now()->gte($this->salesEndAt());
    }

    public function isFinished(): bool
    {
        return $this->waktu_selesai->lte(now());
    }

    /** Jadwal yang sudah dimulai (shift dibuka) atau sudah lewat disimpan sebagai riwayat. */
    public function isEditable(): bool
    {
        return $this->shift ? $this->shift->isOpen() : ! $this->isFinished();
    }

    public function isDeletable(): bool
    {
        return ! $this->shift && ! $this->isFinished();
    }

    public function status(): string
    {
        $shift = $this->shift;

        if ($shift) {
            if (! $shift->isOpen()) {
                return 'hadir';
            }

            return $this->isOverdue() ? 'lewat' : 'bertugas';
        }

        if (now()->lt($this->opensAt())) {
            return 'terjadwal';
        }

        return $this->isFinished() ? 'tidak_hadir' : 'menunggu';
    }

    /** Menit keterlambatan membuka shift dibanding jam mulai jadwal. */
    public function lateMinutes(): int
    {
        if (! $this->shift || $this->shift->waktu_buka->lte($this->waktu_mulai)) {
            return 0;
        }

        return (int) $this->waktu_mulai->diffInMinutes($this->shift->waktu_buka);
    }

    /** Contoh: "07:00–15:00" atau "22:00–06:00 (+1 hari)". */
    public function timeLabel(): string
    {
        $label = $this->waktu_mulai->format('H:i').'–'.$this->waktu_selesai->format('H:i');

        return $this->waktu_selesai->isSameDay($this->waktu_mulai) ? $label : $label.' (+1 hari)';
    }

    /** Contoh: "Senin, 9 November 2026 pukul 07:00–15:00". */
    public function longLabel(): string
    {
        return $this->waktu_mulai->translatedFormat('l, j F Y').' pukul '.$this->timeLabel();
    }

    /** Pesan ketika kasir mencoba masuk di luar jadwal. */
    public static function unavailableMessage(User $kasir): string
    {
        $next = $kasir->schedules()->upcoming()->first();

        if (! $next) {
            return 'Anda tidak memiliki jadwal shift saat ini. Hubungi admin atau pemilik toko.';
        }

        return 'Anda tidak memiliki jadwal shift saat ini. Jadwal berikutnya: '.$next->longLabel()
            .'. Anda dapat masuk mulai pukul '.$next->opensAt()->format('H:i').'.';
    }
}
