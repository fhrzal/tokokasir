<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Toko
    |--------------------------------------------------------------------------
    |
    | Data ini ditampilkan pada struk transaksi dan laporan.
    |
    */

    'nama' => env('TOKO_NAMA', 'TokoKasir'),
    'alamat' => env('TOKO_ALAMAT', ''),
    'telepon' => env('TOKO_TELEPON', ''),
    'rekening' => env('TOKO_REKENING', ''),
    'footer_struk' => env('TOKO_FOOTER', 'Terima kasih atas kunjungan Anda'),

    /*
    |--------------------------------------------------------------------------
    | Gambar QRIS statis
    |--------------------------------------------------------------------------
    |
    | Pembayaran QRIS dicatat manual (tanpa payment gateway). Letakkan gambar
    | QRIS toko pada path berikut (relatif terhadap folder public) agar tampil
    | di halaman pembayaran kasir.
    |
    */

    'qris_image' => env('TOKO_QRIS_IMAGE', 'images/qris.png'),

    'metode_bayar' => [
        'tunai' => 'Tunai',
        'transfer' => 'Transfer',
        'qris' => 'QRIS',
    ],

];
