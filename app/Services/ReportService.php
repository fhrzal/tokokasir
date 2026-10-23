<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kueri laporan untuk pemilik toko (UC-11, UC-12, UC-13).
 */
class ReportService
{
    /**
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $range
     * @param  array{user_id?: int|null, metode_bayar?: string|null}  $filters
     */
    public function transactions(array $range, array $filters = []): Builder
    {
        return Transaction::query()
            ->whereBetween('waktu', $range)
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['metode_bayar'] ?? null, fn ($q, $m) => $q->where('metode_bayar', $m));
    }

    /** Omzet, jumlah transaksi, diskon, rata-rata, dan item terjual. */
    public function salesSummary(array $range, array $filters = []): array
    {
        $row = $this->transactions($range, $filters)
            ->selectRaw('COUNT(*) as transaksi, COALESCE(SUM(total), 0) as omzet, COALESCE(SUM(diskon), 0) as diskon')
            ->first();

        $items = TransactionItem::whereIn('transaction_id', $this->transactions($range, $filters)->select('transaction_id'))
            ->sum('jumlah');

        return [
            'transaksi' => (int) $row->transaksi,
            'omzet' => (float) $row->omzet,
            'diskon' => (float) $row->diskon,
            'rata_rata' => $row->transaksi ? (float) $row->omzet / $row->transaksi : 0,
            'item_terjual' => (int) $items,
        ];
    }

    /** Laba kotor = (penjualan item − HPP historis) − diskon transaksi. */
    public function profitSummary(array $range): array
    {
        $items = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.transaction_id', '=', 'ti.transaction_id')
            ->whereBetween('t.waktu', $range)
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as penjualan, COALESCE(SUM(ti.jumlah * ti.harga_beli), 0) as hpp')
            ->first();

        $diskon = (float) Transaction::whereBetween('waktu', $range)->sum('diskon');
        $omzet = (float) $items->penjualan - $diskon;
        $laba = $omzet - (float) $items->hpp;

        return [
            'penjualan' => (float) $items->penjualan,
            'diskon' => $diskon,
            'omzet' => $omzet,
            'hpp' => (float) $items->hpp,
            'laba' => $laba,
            'margin' => $omzet > 0 ? $laba / $omzet * 100 : 0,
        ];
    }

    /**
     * Rekap harian (omzet, transaksi, laba) dengan tanggal kosong bernilai nol.
     *
     * @return Collection<int, array{tanggal: CarbonImmutable, transaksi: int, omzet: float, laba: float}>
     */
    public function daily(array $range, array $filters = []): Collection
    {
        $sales = $this->transactions($range, $filters)
            ->selectRaw('DATE(waktu) as tanggal, COUNT(*) as transaksi, SUM(total) as omzet, SUM(diskon) as diskon')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $hpp = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.transaction_id', '=', 'ti.transaction_id')
            ->whereBetween('t.waktu', $range)
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('t.user_id', $id))
            ->when($filters['metode_bayar'] ?? null, fn ($q, $m) => $q->where('t.metode_bayar', $m))
            ->selectRaw('DATE(t.waktu) as tanggal, SUM(ti.jumlah * ti.harga_beli) as hpp')
            ->groupBy('tanggal')
            ->pluck('hpp', 'tanggal');

        return collect(CarbonPeriod::create($range[0]->startOfDay(), $range[1]->startOfDay()))
            ->map(function ($date) use ($sales, $hpp) {
                $key = $date->toDateString();
                $omzet = (float) ($sales[$key]->omzet ?? 0);

                return [
                    'tanggal' => CarbonImmutable::parse($key),
                    'transaksi' => (int) ($sales[$key]->transaksi ?? 0),
                    'omzet' => $omzet,
                    'laba' => $omzet - (float) ($hpp[$key] ?? 0),
                ];
            })
            ->values();
    }

    /** Produk dengan jumlah penjualan tertinggi. */
    public function topProducts(array $range, int $limit = 10, array $filters = []): Collection
    {
        return DB::table('transaction_items as ti')
            ->join('transactions as t', 't.transaction_id', '=', 'ti.transaction_id')
            ->join('products as p', 'p.product_id', '=', 'ti.product_id')
            ->whereBetween('t.waktu', $range)
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('t.user_id', $id))
            ->when($filters['metode_bayar'] ?? null, fn ($q, $m) => $q->where('t.metode_bayar', $m))
            ->groupBy('ti.product_id', 'p.kode_produk', 'p.nama_produk', 'p.stok')
            ->selectRaw('ti.product_id, p.kode_produk, p.nama_produk, p.stok,
                SUM(ti.jumlah) as qty, SUM(ti.subtotal) as omzet,
                SUM(ti.subtotal - ti.jumlah * ti.harga_beli) as laba')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }

    /** Rekap per metode bayar: tunai, transfer, QRIS. */
    public function paymentBreakdown(array $range, array $filters = []): Collection
    {
        $rows = $this->transactions($range, $filters)
            ->selectRaw('metode_bayar, COUNT(*) as transaksi, SUM(total) as total')
            ->groupBy('metode_bayar')
            ->get()
            ->keyBy('metode_bayar');

        return collect(config('tokokasir.metode_bayar'))->map(fn ($label, $key) => [
            'metode' => $key,
            'label' => $label,
            'transaksi' => (int) ($rows[$key]->transaksi ?? 0),
            'total' => (float) ($rows[$key]->total ?? 0),
        ])->values();
    }

    /** Jumlah shift, transaksi, omzet, dan total selisih kas per kasir. */
    public function cashierActivity(array $range): Collection
    {
        $tx = Transaction::whereBetween('waktu', $range)
            ->selectRaw('user_id, COUNT(*) as transaksi, SUM(total) as omzet')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $shifts = Shift::whereBetween('waktu_buka', $range)
            ->selectRaw('user_id, COUNT(*) as shift,
                SUM(CASE WHEN selisih < 0 THEN selisih ELSE 0 END) as kurang,
                SUM(CASE WHEN selisih > 0 THEN selisih ELSE 0 END) as lebih,
                SUM(TIMESTAMPDIFF(MINUTE, waktu_buka, COALESCE(waktu_tutup, ?))) as menit', [now()->toDateTimeString()])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return User::where('role', User::ROLE_KASIR)
            ->orderBy('nama')
            ->get()
            ->map(fn (User $u) => [
                'user' => $u,
                'shift' => (int) ($shifts[$u->user_id]->shift ?? 0),
                'jam_kerja' => round(($shifts[$u->user_id]->menit ?? 0) / 60, 1),
                'transaksi' => (int) ($tx[$u->user_id]->transaksi ?? 0),
                'omzet' => (float) ($tx[$u->user_id]->omzet ?? 0),
                'kurang' => (float) ($shifts[$u->user_id]->kurang ?? 0),
                'lebih' => (float) ($shifts[$u->user_id]->lebih ?? 0),
            ])
            ->filter(fn ($row) => $row['user']->status || $row['shift'] > 0);
    }
}
