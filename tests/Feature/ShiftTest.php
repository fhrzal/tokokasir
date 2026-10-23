<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_shift_records_expected_cash_and_difference(): void
    {
        $kasir = $this->user();
        $shift = $this->openShift($kasir, 100000);
        $product = $this->product(['harga_jual' => 10000, 'stok' => 50]);

        $this->actingAs($kasir);
        // Tunai 30.000 masuk laci, QRIS 20.000 tidak memengaruhi kas.
        $this->postJson(route('kasir.pos.store'), ['items' => [['product_id' => $product->product_id, 'qty' => 3]], 'metode_bayar' => 'tunai', 'uang_diterima' => 50000]);
        $this->postJson(route('kasir.pos.store'), ['items' => [['product_id' => $product->product_id, 'qty' => 2]], 'metode_bayar' => 'qris']);

        $this->post(route('kasir.shift.close'), ['kas_akhir' => 128000, 'catatan' => 'Kurang 2 ribu'])
            ->assertRedirect(route('kasir.shift.show', $shift));

        $shift->refresh();
        $this->assertEquals(130000, $shift->kas_seharusnya);
        $this->assertEquals(128000, $shift->kas_akhir);
        $this->assertEquals(-2000, $shift->selisih);
        $this->assertNotNull($shift->waktu_tutup);

        // Setelah shift ditutup, kasir tidak dapat bertransaksi.
        $this->get(route('kasir.pos'))->assertRedirect(route('kasir.shift.create'));
        $this->postJson(route('kasir.pos.store'), ['items' => [['product_id' => $product->product_id, 'qty' => 1]], 'metode_bayar' => 'qris'])
            ->assertStatus(409);
    }
}
