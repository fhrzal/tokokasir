<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal: akun pengguna, katalog produk, supplier,
     * dan riwayat transaksi 30 hari sebagai data demo.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CatalogSeeder::class,
            DemoHistorySeeder::class,
        ]);
    }
}
