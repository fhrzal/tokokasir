<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Satu-satunya pintu perubahan stok produk. Setiap perubahan dicatat
 * ke stock_movements agar riwayat persediaan dapat ditelusuri.
 *
 * Pemanggil wajib menjalankan method ini di dalam DB::transaction()
 * dengan baris produk yang sudah dikunci (lockForUpdate).
 */
class StockService
{
    public function move(
        Product $product,
        int $jumlah,
        string $jenis,
        User $user,
        ?string $referensi = null,
        ?string $keterangan = null,
    ): StockMovement {
        $sebelum = $product->stok;
        $sesudah = $sebelum + $jumlah;

        $product->forceFill(['stok' => $sesudah])->save();

        return StockMovement::create([
            'product_id' => $product->product_id,
            'user_id' => $user->user_id,
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'stok_sebelum' => $sebelum,
            'stok_sesudah' => $sesudah,
            'referensi' => $referensi,
            'keterangan' => $keterangan,
            'waktu' => now(),
        ]);
    }
}
