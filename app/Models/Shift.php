<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $primaryKey = 'shift_id';

    protected $fillable = [
        'user_id',
        'kas_awal',
        'kas_akhir',
        'kas_seharusnya',
        'selisih',
        'catatan',
        'waktu_buka',
        'waktu_tutup',
    ];

    protected function casts(): array
    {
        return [
            'kas_awal' => 'decimal:2',
            'kas_akhir' => 'decimal:2',
            'kas_seharusnya' => 'decimal:2',
            'selisih' => 'decimal:2',
            'waktu_buka' => 'datetime',
            'waktu_tutup' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'shift_id');
    }

    public function isOpen(): bool
    {
        return $this->waktu_tutup === null;
    }

    /**
     * Rekap penjualan shift: jumlah transaksi, omzet, dan total per metode bayar.
     *
     * @return array{jumlah_transaksi:int, omzet:float, per_metode:array<string,float>, tunai:float, kas_seharusnya:float}
     */
    public function summary(): array
    {
        $perMetode = $this->transactions()
            ->selectRaw('metode_bayar, COUNT(*) as jumlah, SUM(total) as total')
            ->groupBy('metode_bayar')
            ->get()
            ->keyBy('metode_bayar');

        $tunai = (float) ($perMetode['tunai']->total ?? 0);

        return [
            'jumlah_transaksi' => (int) $perMetode->sum('jumlah'),
            'omzet' => (float) $perMetode->sum('total'),
            'per_metode' => collect(config('tokokasir.metode_bayar'))
                ->map(fn ($label, $key) => (float) ($perMetode[$key]->total ?? 0))
                ->all(),
            'tunai' => $tunai,
            'kas_seharusnya' => (float) $this->kas_awal + $tunai,
        ];
    }
}
