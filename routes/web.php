<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kasir;
use App\Http\Controllers\Pemilik;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| UC-01 Login / Logout
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Mengalihkan pengguna ke halaman awal sesuai peran.
    Route::get('/', fn () => redirect(auth()->user()->homeRoute()))->name('home');

    // UC-04 Struk: kasir (miliknya sendiri), admin, dan pemilik.
    Route::get('/struk/{transaction}', [ReceiptController::class, 'show'])->name('struk.show');
    Route::get('/struk/{transaction}/pdf', [ReceiptController::class, 'pdf'])->name('struk.pdf');
});

/*
|--------------------------------------------------------------------------
| Kasir: UC-02 s.d. UC-05
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/shift/buka', [Kasir\ShiftController::class, 'create'])->name('shift.create');
    Route::post('/shift/buka', [Kasir\ShiftController::class, 'store'])->name('shift.store');
    Route::get('/shift/{shift}/ringkasan', [Kasir\ShiftController::class, 'show'])->name('shift.show');

    Route::middleware('shift.open')->group(function () {
        Route::get('/transaksi', [Kasir\PosController::class, 'index'])->name('pos');
        Route::get('/transaksi/produk', [Kasir\PosController::class, 'products'])->name('pos.products');
        Route::post('/transaksi', [Kasir\PosController::class, 'store'])->name('pos.store');
        Route::get('/riwayat', [Kasir\PosController::class, 'history'])->name('history');

        Route::get('/shift/tutup', [Kasir\ShiftController::class, 'edit'])->name('shift.edit');
        Route::post('/shift/tutup', [Kasir\ShiftController::class, 'close'])->name('shift.close');
    });
});

/*
|--------------------------------------------------------------------------
| Admin: UC-06 s.d. UC-10
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::resource('kategori', Admin\CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['kategori' => 'category']);

    Route::resource('produk', Admin\ProductController::class)
        ->except(['show', 'destroy'])
        ->parameters(['produk' => 'product']);
    Route::patch('/produk/{product}/status', [Admin\ProductController::class, 'toggle'])->name('produk.toggle');

    Route::resource('supplier', Admin\SupplierController::class)
        ->except(['show'])
        ->parameters(['supplier' => 'supplier']);

    Route::resource('barang-masuk', Admin\PurchaseController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['barang-masuk' => 'purchase']);

    Route::get('/stok', [Admin\StockController::class, 'index'])->name('stok.index');
    Route::post('/stok/penyesuaian', [Admin\StockController::class, 'adjust'])->name('stok.adjust');

    Route::resource('pengguna', Admin\UserController::class)
        ->except(['show', 'destroy'])
        ->parameters(['pengguna' => 'user']);
    Route::patch('/pengguna/{user}/status', [Admin\UserController::class, 'toggle'])->name('pengguna.toggle');
});

/*
|--------------------------------------------------------------------------
| Pemilik Toko: UC-11 s.d. UC-13
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pemilik'])->prefix('pemilik')->name('pemilik.')->group(function () {
    Route::get('/dashboard', Pemilik\DashboardController::class)->name('dashboard');
    Route::get('/laporan/penjualan', [Pemilik\ReportController::class, 'sales'])->name('laporan.penjualan');
    Route::get('/laporan/stok', [Pemilik\ReportController::class, 'stock'])->name('laporan.stok');
    Route::get('/laporan/laba-kasir', [Pemilik\ReportController::class, 'profit'])->name('laporan.laba');
});
