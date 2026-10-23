<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PurchaseService;
use App\Services\StockService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mensimulasikan operasional toko selama 30 hari terakhir melalui service
 * aplikasi (bukan insert langsung), sehingga stok, pergerakan stok, shift,
 * dan laporan konsisten satu sama lain.
 */
class DemoHistorySeeder extends Seeder
{
    private const DAYS = 30;

    /** Produk yang sengaja tidak di-restock agar peringatan stok terlihat. */
    private const NEGLECTED = ['8998866104005', '8993093665013', '8993560025012'];

    private Collection $products;

    /** @var array<int, int> product_id => bobot popularitas */
    private array $weights = [];

    public function __construct(
        private CheckoutService $checkout,
        private PurchaseService $purchases,
        private StockService $stock,
    ) {}

    public function run(): void
    {
        mt_srand(2026);

        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $cashiers = User::where('role', User::ROLE_KASIR)->orderBy('user_id')->get();
        $suppliers = Supplier::orderBy('supplier_id')->get();
        $this->products = Product::orderBy('product_id')->get()->keyBy('product_id');

        foreach (CatalogSeeder::PRODUCTS as $rows) {
            foreach ($rows as $row) {
                $this->weights[$this->products->firstWhere('kode_produk', $row[0])->product_id] = $row[5];
            }
        }

        $realNow = CarbonImmutable::now();
        $start = $realNow->startOfDay()->subDays(self::DAYS);

        // Stok awal dari beberapa supplier.
        $this->at($start->setTime(6, 30));
        foreach ($this->products->chunk(10) as $i => $chunk) {
            $this->purchases->record($admin, [
                'supplier_id' => $suppliers[$i % $suppliers->count()]->supplier_id,
                'tanggal' => now(),
                'no_faktur' => 'INV-'.$start->format('ymd').'-'.($i + 1),
                'keterangan' => 'Stok awal toko',
                'update_harga_beli' => true,
                'items' => $chunk->map(fn (Product $p) => [
                    'product_id' => $p->product_id,
                    'qty' => $this->targetStock($p),
                    'harga_beli' => (float) $p->harga_beli,
                ])->values()->all(),
            ]);
        }

        // Hari ini ikut disimulasikan bila seeder dijalankan setelah jam 09.00.
        $lastDay = $realNow->hour >= 9 ? self::DAYS : self::DAYS - 1;

        for ($day = 0; $day <= $lastDay; $day++) {
            $date = $start->addDays($day);
            $isToday = $date->isSameDay($realNow);

            if ($day > 0 && $day % 6 === 0) {
                $this->restock($admin, $suppliers, $date->setTime(6, 45));
            }

            if ($day === 12) {
                $this->adjust($admin, '2000000000017', -3, 'Barang rusak: telur pecah saat bongkar muat', $date->setTime(9, 15));
            }
            if ($day === 20) {
                $this->adjust($admin, '8996001600146', -2, 'Barang kedaluwarsa', $date->setTime(10, 5));
            }

            // Hasil stok opname untuk produk yang tidak di-restock.
            if ($day === $lastDay) {
                $this->setStock($admin, '8998866104005', 0, 'Stok opname: kemasan bocor', $date->setTime(6, 50));
                $this->setStock($admin, '8993093665013', 6, 'Stok opname', $date->setTime(6, 50));
                $this->setStock($admin, '8993560025012', 2, 'Stok opname', $date->setTime(6, 50));
            }

            $busy = $date->isWeekend() ? 1.4 : 1.0;

            foreach ($cashiers as $index => $cashier) {
                // Setiap kasir libur satu hari dalam seminggu.
                if ($date->dayOfWeek === ($index === 0 ? 1 : 3)) {
                    continue;
                }

                $open = $date->setTime($index === 0 ? 7 : 14, mt_rand(0, 10));
                $close = $date->setTime($index === 0 ? 14 : 21, mt_rand(0, 15));

                if ($isToday) {
                    // Hari ini: hanya kasir kedua yang sudah menyelesaikan shift pagi,
                    // kasir pertama belum membuka shift (untuk demo alur buka shift).
                    if ($index !== 1) {
                        continue;
                    }
                    $open = $date->setTime(7, 0);
                    $close = $realNow->subMinutes(10);
                }

                $this->simulateShift($cashier, $open, $close, (int) round(mt_rand(10, 18) * $busy));
            }
        }

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }

    private function simulateShift(User $cashier, CarbonImmutable $open, CarbonImmutable $close, int $count): void
    {
        $this->at($open);
        $shift = $cashier->shifts()->create(['kas_awal' => [150000, 200000, 250000][mt_rand(0, 2)], 'waktu_buka' => now()]);

        $span = max(1, $open->diffInSeconds($close) - 600);
        $times = collect(range(1, $count))->map(fn () => mt_rand(300, $span))->sort()->values();

        foreach ($times as $offset) {
            $this->at($open->addSeconds($offset));
            $this->sell($cashier, $shift);
        }

        $this->at($close);
        $summary = $shift->summary();
        $roll = mt_rand(1, 100);
        $selisih = $roll <= 85 ? 0 : ($roll <= 94 ? -500 * mt_rand(1, 10) : 500 * mt_rand(1, 4));

        $shift->update([
            'kas_akhir' => $summary['kas_seharusnya'] + $selisih,
            'kas_seharusnya' => $summary['kas_seharusnya'],
            'selisih' => $selisih,
            'catatan' => $selisih < 0 ? 'Kemungkinan salah memberi kembalian' : null,
            'waktu_tutup' => now(),
        ]);
    }

    private function sell(User $cashier, Shift $shift): void
    {
        $items = [];
        $picks = mt_rand(1, 100) <= 45 ? 1 : mt_rand(2, 5);

        for ($i = 0; $i < $picks; $i++) {
            $product = $this->products[$this->weightedPick()];
            $product->refresh();

            $qty = mt_rand(1, 100) <= 70 ? 1 : mt_rand(2, 4);
            if ($product->stok < $qty || isset($items[$product->product_id])) {
                continue;
            }

            $items[$product->product_id] = ['product_id' => $product->product_id, 'qty' => $qty];
        }

        if (! $items) {
            return;
        }

        $subtotal = collect($items)->sum(fn ($i) => $i['qty'] * (float) $this->products[$i['product_id']]->harga_jual);
        $diskon = mt_rand(1, 100) <= 6 ? min(500 * mt_rand(1, 6), $subtotal * 0.1) : 0;
        $total = $subtotal - $diskon;

        $roll = mt_rand(1, 100);
        $metode = $roll <= 62 ? 'tunai' : ($roll <= 84 ? 'qris' : 'transfer');

        try {
            $this->checkout->checkout($cashier, $shift, [
                'items' => array_values($items),
                'diskon' => $diskon,
                'metode_bayar' => $metode,
                'uang_diterima' => $metode === 'tunai' ? $this->cashPaid($total) : null,
                'referensi_bayar' => $metode === 'tunai' ? null : strtoupper(substr(md5((string) mt_rand()), 0, 10)),
            ]);
        } catch (ValidationException) {
            // Stok tidak mencukupi: lewati transaksi ini.
        }
    }

    private function restock(User $admin, Collection $suppliers, CarbonImmutable $time): void
    {
        $this->at($time);

        $items = Product::whereNotIn('kode_produk', self::NEGLECTED)->get()
            ->filter(fn (Product $p) => $p->stok < $this->targetStock($p) * 0.6)
            ->map(fn (Product $p) => [
                'product_id' => $p->product_id,
                'qty' => $this->targetStock($p) - $p->stok,
                'harga_beli' => (float) $p->harga_beli,
            ])
            ->values();

        foreach ($items->chunk(8) as $i => $chunk) {
            $this->purchases->record($admin, [
                'supplier_id' => $suppliers[($time->day + $i) % $suppliers->count()]->supplier_id,
                'tanggal' => now(),
                'no_faktur' => 'INV-'.$time->format('ymd').'-'.($i + 1),
                'update_harga_beli' => true,
                'items' => $chunk->values()->all(),
            ]);
        }
    }

    private function adjust(User $admin, string $kode, int $jumlah, string $keterangan, CarbonImmutable $time): void
    {
        $this->at($time);

        DB::transaction(function () use ($admin, $kode, $jumlah, $keterangan) {
            $product = Product::where('kode_produk', $kode)->lockForUpdate()->first();
            $jumlah = max($jumlah, -$product->stok);

            if ($jumlah !== 0) {
                $this->stock->move($product, $jumlah, 'penyesuaian', $admin, null, $keterangan);
            }
        });
    }

    private function setStock(User $admin, string $kode, int $target, string $keterangan, CarbonImmutable $time): void
    {
        $product = Product::where('kode_produk', $kode)->first();
        $this->adjust($admin, $kode, $target - $product->stok, $keterangan, $time);
    }

    private function targetStock(Product $product): int
    {
        return (int) (ceil(($this->weights[$product->product_id] * 14 + $product->stok_minimum * 2) / 6) * 6);
    }

    private function weightedPick(): int
    {
        $roll = mt_rand(1, array_sum($this->weights));

        foreach ($this->weights as $productId => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $productId;
            }
        }

        return array_key_first($this->weights);
    }

    /** Pembayaran tunai realistis: uang pas atau dibulatkan ke pecahan uang. */
    private function cashPaid(float $total): float
    {
        $step = [0, 5000, 10000, 20000, 50000, 100000][mt_rand(0, 5)];

        return $step === 0 ? $total : max($total, ceil($total / $step) * $step);
    }

    private function at(CarbonImmutable $time): void
    {
        Carbon::setTestNow($time);
        CarbonImmutable::setTestNow($time);
    }
}
