<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private StockService $stock) {}

    /**
     * Menyimpan transaksi penjualan secara atomik: membuat transaksi dan
     * item, mengurangi stok, dan mencatat pergerakan stok. Harga selalu
     * diambil dari database, bukan dari browser.
     *
     * @param  array{items: array<int, array{product_id:int, qty:int}>, diskon?: float, metode_bayar: string, uang_diterima?: float|null, referensi_bayar?: string|null, client_ref?: string|null}  $data
     */
    public function checkout(User $kasir, Shift $shift, array $data): Transaction
    {
        $clientRef = $data['client_ref'] ?? null;

        // Permintaan yang sama terkirim dua kali (mis. tombol ditekan ganda).
        if ($clientRef && $existing = Transaction::where('client_ref', $clientRef)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->store($kasir, $shift, $data, $clientRef));
        } catch (QueryException $e) {
            if ($clientRef && ($e->errorInfo[1] ?? null) === 1062
                && $existing = Transaction::where('client_ref', $clientRef)->first()) {
                return $existing;
            }

            throw $e;
        }
    }

    private function store(User $kasir, Shift $shift, array $data, ?string $clientRef): Transaction
    {
        $qtyPerProduct = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($rows) => (int) $rows->sum('qty'));

        $products = Product::whereIn('product_id', $qtyPerProduct->keys())
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        $errors = [];
        $subtotal = 0;

        foreach ($qtyPerProduct as $productId => $qty) {
            $product = $products->get($productId);

            if (! $product || ! $product->status_aktif) {
                $errors[] = 'Produk tidak ditemukan atau sudah tidak aktif.';

                continue;
            }

            if ($product->stok < $qty) {
                $errors[] = "Stok {$product->nama_produk} tidak cukup (tersisa {$product->stok}).";

                continue;
            }

            $subtotal += $qty * (float) $product->harga_jual;
        }

        if ($errors) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        $diskon = min(max(0, round((float) ($data['diskon'] ?? 0), 2)), $subtotal);
        $total = round($subtotal - $diskon, 2);
        $metode = $data['metode_bayar'];

        if ($metode === 'tunai') {
            $uangDiterima = round((float) ($data['uang_diterima'] ?? 0), 2);

            if ($uangDiterima < $total) {
                throw ValidationException::withMessages([
                    'uang_diterima' => 'Uang diterima kurang dari total belanja.',
                ]);
            }
        } else {
            // Transfer / QRIS dicatat sesuai total (diverifikasi manual oleh kasir).
            $uangDiterima = $total;
        }

        $transaction = Transaction::create([
            'kode_transaksi' => (string) Str::uuid(),
            'client_ref' => $clientRef,
            'shift_id' => $shift->shift_id,
            'user_id' => $kasir->user_id,
            'subtotal' => $subtotal,
            'diskon' => $diskon,
            'total' => $total,
            'metode_bayar' => $metode,
            'status_bayar' => 'lunas',
            'uang_diterima' => $uangDiterima,
            'kembalian' => round($uangDiterima - $total, 2),
            'referensi_bayar' => $data['referensi_bayar'] ?? null,
            'waktu' => now(),
        ]);

        // Nomor struk berurutan berdasarkan ID agar aman dari tabrakan antar kasir.
        $transaction->kode_transaksi = 'TRX'.$transaction->waktu->format('ymd').'-'
            .str_pad((string) $transaction->transaction_id, 5, '0', STR_PAD_LEFT);
        $transaction->save();

        foreach ($qtyPerProduct as $productId => $qty) {
            $product = $products->get($productId);

            $transaction->items()->create([
                'product_id' => $product->product_id,
                'nama_produk' => $product->nama_produk,
                'jumlah' => $qty,
                'harga' => $product->harga_jual,
                'harga_beli' => $product->harga_beli,
                'subtotal' => $qty * (float) $product->harga_jual,
            ]);

            $this->stock->move($product, -$qty, 'penjualan', $kasir, $transaction->kode_transaksi);
        }

        return $transaction;
    }
}
