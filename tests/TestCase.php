<?php

namespace Tests;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function user(string $role = User::ROLE_KASIR): User
    {
        return User::factory()->role($role)->create();
    }

    protected function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['nama_kategori' => 'Umum']);

        return Product::create($attributes + [
            'category_id' => $category->category_id,
            'kode_produk' => (string) fake()->unique()->numerify('899#########'),
            'nama_produk' => fake()->words(2, true),
            'harga_beli' => 2000,
            'harga_jual' => 3000,
            'stok' => 10,
            'stok_minimum' => 2,
            'status_aktif' => true,
        ]);
    }

    protected function openShift(User $kasir, float $kasAwal = 100000): Shift
    {
        return $kasir->shifts()->create(['kas_awal' => $kasAwal, 'waktu_buka' => now()]);
    }
}
