<?php

namespace Tests;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\ShiftSchedule;
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

    protected function openShift(User $kasir, float $kasAwal = 100000, ?ShiftSchedule $schedule = null): Shift
    {
        return $kasir->shifts()->create([
            'schedule_id' => $schedule?->schedule_id,
            'kas_awal' => $kasAwal,
            'waktu_buka' => now(),
        ]);
    }

    /**
     * Jadwal kasir relatif terhadap waktu sekarang (dalam menit).
     * Bawaan: dimulai 1 jam lalu dan berakhir 7 jam lagi.
     */
    protected function schedule(User $kasir, int $startOffset = -60, int $endOffset = 420): ShiftSchedule
    {
        return $kasir->schedules()->create([
            'waktu_mulai' => now()->addMinutes($startOffset)->startOfMinute(),
            'waktu_selesai' => now()->addMinutes($endOffset)->startOfMinute(),
            'dibuat_oleh' => User::where('role', User::ROLE_ADMIN)->value('user_id') ?? $this->user(User::ROLE_ADMIN)->user_id,
        ]);
    }
}
