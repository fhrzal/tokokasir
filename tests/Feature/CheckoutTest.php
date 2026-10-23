<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_must_open_shift_before_transacting(): void
    {
        $kasir = $this->user();

        $this->actingAs($kasir)->get(route('kasir.pos'))->assertRedirect(route('kasir.shift.create'));

        $this->post(route('kasir.shift.store'), ['kas_awal' => 150000])->assertRedirect(route('kasir.pos'));
        $this->assertDatabaseHas('shifts', ['user_id' => $kasir->user_id, 'kas_awal' => 150000, 'waktu_tutup' => null]);
        $this->get(route('kasir.pos'))->assertOk();
    }

    public function test_opening_shift_twice_keeps_single_open_shift(): void
    {
        $kasir = $this->user();
        $this->actingAs($kasir);

        $this->post(route('kasir.shift.store'), ['kas_awal' => 100000]);
        $this->post(route('kasir.shift.store'), ['kas_awal' => 200000]);

        $this->assertSame(1, $kasir->shifts()->whereNull('waktu_tutup')->count());
    }

    public function test_checkout_uses_database_prices_and_reduces_stock(): void
    {
        $kasir = $this->user();
        $shift = $this->openShift($kasir);
        $mie = $this->product(['harga_jual' => 3500, 'harga_beli' => 2700, 'stok' => 20]);
        $air = $this->product(['harga_jual' => 4000, 'harga_beli' => 3000, 'stok' => 5]);

        $response = $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [
                ['product_id' => $mie->product_id, 'qty' => 3, 'harga' => 1], // harga dari browser diabaikan
                ['product_id' => $air->product_id, 'qty' => 2],
            ],
            'diskon' => 500,
            'metode_bayar' => 'tunai',
            'uang_diterima' => 20000,
        ]);

        $response->assertCreated()->assertJsonStructure(['kode_transaksi', 'struk_url']);

        $trx = Transaction::firstOrFail();
        $this->assertEquals(18500, $trx->subtotal);
        $this->assertEquals(500, $trx->diskon);
        $this->assertEquals(18000, $trx->total);
        $this->assertEquals(2000, $trx->kembalian);
        $this->assertSame($shift->shift_id, $trx->shift_id);
        $this->assertMatchesRegularExpression('/^TRX\d{6}-\d{5}$/', $trx->kode_transaksi);

        $this->assertSame(17, $mie->fresh()->stok);
        $this->assertSame(3, $air->fresh()->stok);
        $this->assertDatabaseHas('transaction_items', ['product_id' => $mie->product_id, 'jumlah' => 3, 'harga' => 3500, 'harga_beli' => 2700]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $mie->product_id, 'jenis' => 'penjualan', 'jumlah' => -3,
            'stok_sebelum' => 20, 'stok_sesudah' => 17, 'referensi' => $trx->kode_transaksi,
        ]);
    }

    public function test_same_client_ref_does_not_create_duplicate_transaction(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $product = $this->product(['stok' => 10]);

        $payload = [
            'items' => [['product_id' => $product->product_id, 'qty' => 1]],
            'metode_bayar' => 'qris',
            'client_ref' => (string) Str::uuid(),
        ];

        $first = $this->actingAs($kasir)->postJson(route('kasir.pos.store'), $payload)->assertCreated();
        $second = $this->postJson(route('kasir.pos.store'), $payload)->assertCreated();

        $this->assertSame($first->json('kode_transaksi'), $second->json('kode_transaksi'));
        $this->assertSame(1, Transaction::count());
        $this->assertSame(9, $product->fresh()->stok);
    }

    public function test_insufficient_stock_is_rejected_without_side_effects(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $ok = $this->product(['stok' => 10]);
        $low = $this->product(['stok' => 1, 'nama_produk' => 'Gula 1kg']);

        $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [
                ['product_id' => $ok->product_id, 'qty' => 2],
                ['product_id' => $low->product_id, 'qty' => 2],
            ],
            'metode_bayar' => 'transfer',
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(10, $ok->fresh()->stok);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_inactive_product_cannot_be_sold(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $product = $this->product(['status_aktif' => false]);

        $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [['product_id' => $product->product_id, 'qty' => 1]],
            'metode_bayar' => 'qris',
        ])->assertUnprocessable();
    }

    public function test_cash_payment_must_cover_total(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $product = $this->product(['harga_jual' => 10000]);

        $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [['product_id' => $product->product_id, 'qty' => 1]],
            'metode_bayar' => 'tunai',
            'uang_diterima' => 5000,
        ])->assertUnprocessable()->assertJsonValidationErrors('uang_diterima');
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $product = $this->product(['harga_jual' => 10000]);

        $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [['product_id' => $product->product_id, 'qty' => 1]],
            'diskon' => 99999,
            'metode_bayar' => 'qris',
        ])->assertCreated();

        $this->assertEquals(0, Transaction::first()->total);
    }

    public function test_kasir_cannot_view_other_kasir_receipt(): void
    {
        $siti = $this->user();
        $budi = $this->user();
        $this->openShift($siti);
        $product = $this->product();

        $this->actingAs($siti)->postJson(route('kasir.pos.store'), [
            'items' => [['product_id' => $product->product_id, 'qty' => 1]],
            'metode_bayar' => 'qris',
        ]);
        $trx = Transaction::first();

        $this->get(route('struk.show', $trx))->assertOk()->assertSee($trx->kode_transaksi);
        $this->get(route('struk.pdf', $trx))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($budi)->get(route('struk.show', $trx))->assertForbidden();
        $this->actingAs($this->user('pemilik'))->get(route('struk.show', $trx))->assertOk();
    }
}
