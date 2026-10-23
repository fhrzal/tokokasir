<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_gross_profit_uses_historical_cost_and_discount(): void
    {
        $kasir = $this->user();
        $this->openShift($kasir);
        $product = $this->product(['harga_beli' => 6000, 'harga_jual' => 10000, 'stok' => 10]);

        $this->actingAs($kasir)->postJson(route('kasir.pos.store'), [
            'items' => [['product_id' => $product->product_id, 'qty' => 3]],
            'diskon' => 2000,
            'metode_bayar' => 'qris',
        ])->assertCreated();

        // Harga beli berubah setelah transaksi tidak memengaruhi laba historis.
        $product->update(['harga_beli' => 9000]);

        $today = [CarbonImmutable::today(), CarbonImmutable::today()->endOfDay()];
        $profit = app(ReportService::class)->profitSummary($today);

        $this->assertEquals(30000, $profit['penjualan']);
        $this->assertEquals(28000, $profit['omzet']);
        $this->assertEquals(18000, $profit['hpp']);
        $this->assertEquals(10000, $profit['laba']);
    }

    public function test_owner_report_pages_render(): void
    {
        $this->product(['stok' => 0]);
        $this->actingAs($this->user(User::ROLE_PEMILIK));

        $this->get(route('pemilik.dashboard'))->assertOk();
        $this->get(route('pemilik.laporan.penjualan', ['periode' => '7_hari']))->assertOk();
        $this->get(route('pemilik.laporan.penjualan', ['periode' => 'custom', 'dari' => '2026-01-01', 'sampai' => '2026-01-31']))->assertOk();
        $this->get(route('pemilik.laporan.stok', ['status' => 'habis']))->assertOk()->assertSee('Habis');
        $this->get(route('pemilik.laporan.laba'))->assertOk();
        $this->get(route('pemilik.laporan.penjualan', ['export' => 'csv']))->assertOk()->assertDownload();
    }
}
