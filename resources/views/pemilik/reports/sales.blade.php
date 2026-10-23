@extends('layouts.app')
@section('title', 'Laporan Penjualan')

@section('content')
<x-page-header title="Laporan Penjualan" :subtitle="'Periode ' . $range->label()">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'page' => null]) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" />Ekspor CSV</a>
    <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak</button>
</x-page-header>

<x-date-filter :range="$range" :action="route('pemilik.laporan.penjualan')">
    <div>
        <label for="kasir" class="label">Kasir</label>
        <select id="kasir" name="kasir" class="input w-44">
            <option value="">Semua kasir</option>
            @foreach ($cashiers as $cashier)
                <option value="{{ $cashier->user_id }}" @selected(request('kasir') == $cashier->user_id)>{{ $cashier->nama }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="metode" class="label">Metode bayar</label>
        <select id="metode" name="metode" class="input w-36">
            <option value="">Semua</option>
            @foreach (config('tokokasir.metode_bayar') as $key => $label)
                <option value="{{ $key }}" @selected(request('metode') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</x-date-filter>

<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat label="Omzet" :value="rupiah($summary['omzet'])" icon="banknotes" :hint="'Setelah diskon ' . rupiah($summary['diskon'])" />
    <x-stat label="Jumlah transaksi" :value="number_format($summary['transaksi'], 0, ',', '.')" icon="receipt" tone="slate" />
    <x-stat label="Rata-rata per transaksi" :value="rupiah($summary['rata_rata'])" icon="cart" tone="violet" />
    <x-stat label="Item terjual" :value="number_format($summary['item_terjual'], 0, ',', '.')" icon="cube" tone="green" />
</div>

@if ($daily->count() > 1)
    <div class="card mb-6 p-5">
        <h2 class="card-title">Omzet harian</h2>
        <x-rupiah-chart :labels="$daily->map(fn ($d) => $d['tanggal']->translatedFormat('d M'))->all()"
                        :series="[['label' => 'Omzet', 'data' => $daily->pluck('omzet')->all(), 'type' => 'bar']]" />
    </div>
@endif

<div class="mb-6 grid gap-6 lg:grid-cols-5">
    <div class="card lg:col-span-2">
        <div class="card-header"><h2 class="card-title">Rekap metode pembayaran</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Metode</th><th class="num">Transaksi</th><th class="num">Total</th><th class="num">Porsi</th></tr></thead>
                <tbody>
                @foreach ($payments as $pay)
                    <tr>
                        <td class="font-medium">{{ $pay['label'] }}</td>
                        <td class="num">{{ $pay['transaksi'] }}</td>
                        <td class="num font-semibold">{{ rupiah($pay['total']) }}</td>
                        <td class="num text-slate-500">{{ $summary['omzet'] > 0 ? number_format($pay['total'] / $summary['omzet'] * 100, 1, ',', '.') : 0 }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card lg:col-span-3">
        <div class="card-header"><h2 class="card-title">Detail produk terjual</h2><span class="text-xs text-slate-500">15 teratas</span></div>
        @if ($products->isEmpty())
            <x-empty icon="cube" message="Tidak ada produk terjual." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Produk</th><th class="num">Qty</th><th class="num">Penjualan</th><th class="num">Laba kotor</th></tr></thead>
                    <tbody>
                    @foreach ($products as $row)
                        <tr>
                            <td>
                                <p class="font-medium text-slate-800">{{ $row->nama_produk }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ $row->kode_produk }}</p>
                            </td>
                            <td class="num font-semibold">{{ number_format($row->qty, 0, ',', '.') }}</td>
                            <td class="num">{{ rupiah($row->omzet) }}</td>
                            <td class="num text-slate-600">{{ rupiah($row->laba) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Rekap transaksi</h2><span class="text-xs text-slate-500">{{ $transactions->total() }} transaksi</span></div>
    @if ($transactions->isEmpty())
        <x-empty icon="receipt" message="Tidak ada transaksi pada periode ini." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>No. transaksi</th><th>Waktu</th><th>Kasir</th><th>Metode</th><th class="num">Item</th><th class="num">Diskon</th><th class="num">Total</th><th class="no-print"></th></tr></thead>
                <tbody>
                @foreach ($transactions as $trx)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $trx->kode_transaksi }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $trx->waktu->format('d/m/Y H:i') }}</td>
                        <td>{{ $trx->user->nama }}</td>
                        <td><span class="badge {{ $trx->metode_bayar === 'tunai' ? 'badge-green' : ($trx->metode_bayar === 'qris' ? 'badge-violet' : 'badge-blue') }}">{{ $trx->metodeLabel() }}</span></td>
                        <td class="num">{{ $trx->items_sum_jumlah }}</td>
                        <td class="num text-slate-500">{{ $trx->diskon > 0 ? rupiah($trx->diskon) : '–' }}</td>
                        <td class="num font-semibold">{{ rupiah($trx->total) }}</td>
                        <td class="no-print text-right"><a href="{{ route('struk.show', $trx) }}" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" />Detail</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="no-print border-t border-slate-100 px-4 py-3">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
