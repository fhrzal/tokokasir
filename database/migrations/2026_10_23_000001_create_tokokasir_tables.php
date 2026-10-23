<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema database TokoKasir sesuai ERD pada proposal (Gambar 3).
 * Kolom tambahan di luar ERD diberi komentar "tambahan".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('nama_kategori', 100)->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('category_id')->constrained('categories', 'category_id')->restrictOnDelete();
            $table->string('kode_produk', 30)->unique();
            $table->string('nama_produk', 150)->index();
            $table->decimal('harga_beli', 12, 2)->default(0);
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->integer('stok_minimum')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('nama_supplier', 120);
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id('shift_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->decimal('kas_awal', 12, 2);
            $table->decimal('kas_akhir', 12, 2)->nullable();
            $table->decimal('kas_seharusnya', 12, 2)->nullable(); // tambahan: kas awal + penjualan tunai
            $table->decimal('selisih', 12, 2)->nullable();        // tambahan: kas akhir - kas seharusnya
            $table->string('catatan', 255)->nullable();           // tambahan
            $table->dateTime('waktu_buka');
            $table->dateTime('waktu_tutup')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'waktu_tutup']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('kode_transaksi', 40)->unique();       // tambahan: nomor struk
            $table->uuid('client_ref')->nullable()->unique();     // tambahan: mencegah transaksi ganda
            $table->foreignId('shift_id')->constrained('shifts', 'shift_id')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->decimal('subtotal', 12, 2);                   // tambahan: total sebelum diskon
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('metode_bayar', 30);                   // tunai | transfer | qris
            $table->string('status_bayar', 20)->default('lunas');
            $table->decimal('uang_diterima', 12, 2);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->string('referensi_bayar', 100)->nullable();   // tambahan: no. ref transfer/QRIS
            $table->dateTime('waktu')->index();
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->restrictOnDelete();
            $table->string('nama_produk', 150);                   // tambahan: nama saat transaksi
            $table->integer('jumlah');
            $table->decimal('harga', 12, 2);                      // harga jual historis
            $table->decimal('harga_beli', 12, 2);                 // harga beli historis (untuk laba)
            $table->decimal('subtotal', 12, 2);                   // tambahan
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id('purchase_id');
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->string('no_faktur', 50)->nullable();          // tambahan: nomor nota supplier
            $table->dateTime('tanggal')->index();
            $table->decimal('total', 12, 2);
            $table->string('keterangan', 255)->nullable();        // tambahan
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id('purchase_item_id');
            $table->foreignId('purchase_id')->constrained('purchases', 'purchase_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('harga_beli', 12, 2);
            $table->decimal('subtotal', 12, 2);                   // tambahan
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id('movement_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->string('jenis', 20)->index();                 // awal | masuk | penjualan | penyesuaian
            $table->integer('jumlah');                            // positif = bertambah, negatif = berkurang
            $table->integer('stok_sebelum');                      // tambahan
            $table->integer('stok_sesudah');                      // tambahan
            $table->string('referensi', 50)->nullable();          // tambahan: kode transaksi / no. pembelian
            $table->string('keterangan', 150)->nullable();
            $table->dateTime('waktu')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
