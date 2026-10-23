<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stock) {}

    /**
     * Mencatat barang masuk dari supplier: menyimpan pembelian, menambah
     * stok, mencatat pergerakan stok, dan (opsional) memperbarui harga beli.
     *
     * @param  array{supplier_id:int, tanggal:string, no_faktur?:string|null, keterangan?:string|null, update_harga_beli?:bool, items: array<int, array{product_id:int, qty:int, harga_beli:float}>}  $data
     */
    public function record(User $admin, array $data): Purchase
    {
        return DB::transaction(function () use ($admin, $data) {
            $items = collect($data['items']);

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $admin->user_id,
                'no_faktur' => $data['no_faktur'] ?? null,
                'tanggal' => $data['tanggal'],
                'total' => $items->sum(fn ($i) => $i['qty'] * $i['harga_beli']),
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $products = Product::whereIn('product_id', $items->pluck('product_id'))
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                $purchase->items()->create([
                    'product_id' => $product->product_id,
                    'qty' => $item['qty'],
                    'harga_beli' => $item['harga_beli'],
                    'subtotal' => $item['qty'] * $item['harga_beli'],
                ]);

                if (! empty($data['update_harga_beli'])) {
                    $product->harga_beli = $item['harga_beli'];
                }

                $this->stock->move($product, (int) $item['qty'], 'masuk', $admin, $purchase->kode(),
                    'Pembelian dari '.$purchase->supplier->nama_supplier);
            }

            return $purchase;
        });
    }
}
