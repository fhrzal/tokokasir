@extends('layouts.app')
@section('title', 'Dashboard Toko')

@section('content')
<x-page-header title="Dashboard Toko" :subtitle="now()->translatedFormat('l, d F Y')">
    <a href="{{ route('pemilik.laporan.penjualan') }}" class="btn btn-secondary"><x-icon name="chart" class="size-4" />Laporan Penjualan</a>
</x-page-header>

<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat label="Omzet hari ini" :value="rupiah($today['omzet'])" icon="banknotes">
        <x-slot:footer><x-delta :current="$today['omzet']" :previous="$yesterday['omzet']" /></x-slot:footer>
    </x-stat>
    <x-stat label="Transaksi hari ini" :value="number_format($today['transaksi'], 0, ',', '.')" icon="receipt" tone="slate">
        <x-slot:footer><x-delta :current="$today['transaksi']" :previous="$yesterday['transaksi']" /></x-slot:footer>
    </x-stat>
    <x-stat label="Laba kotor hari ini" :value="rupiah($today['laba'])" icon="profit" tone="green">
        <x-slot:footer><x-delta :current="$today['laba']" :previous="$yesterday['laba']" /></x-slot:footer>
    </x-stat>
    <x-stat label="Rata-rata per transaksi" :value="rupiah($today['rata_rata'])" icon="cart" tone="violet"
            :hint="$today['item_terjual'] . ' item terjual hari ini'" />
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-3">
    <div class="card p-5 xl:col-span-2">
        <div class="mb-1">
            <h2 class="card-title">Omzet &amp; laba kotor</h2>
            <p class="text-xs text-slate-500">14 hari terakhir</p>
        </div>
        <x-rupiah-chart :labels="$chart['labels']" :series="[
            ['label' => 'Omzet', 'data' => $chart['omzet'], 'type' => 'bar'],
            ['label' => 'Laba kotor', 'data' => $chart['laba'], 'type' => 'line'],
        ]" />
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Bulan ini</h2>
                <p class="text-xs text-slate-500">{{ now()->translatedFormat('F Y') }}</p>
            </div>
            <a href="{{ route('pemilik.laporan.laba') }}" class="text-sm font-medium text-brand-600 hover:underline">Detail</a>
        </div>
        <dl class="grid grid-cols-2 gap-4 px-5 py-4">
            <div><dt class="text-xs text-slate-500">Omzet</dt><dd class="text-lg font-bold text-slate-900">{{ rupiah($month['omzet']) }}</dd></div>
            <div><dt class="text-xs text-slate-500">Laba kotor</dt><dd class="text-lg font-bold text-slate-900">{{ rupiah($month['laba']) }}</dd></div>
            <div><dt class="text-xs text-slate-500">HPP</dt><dd class="font-semibold text-slate-700">{{ rupiah($month['hpp']) }}</dd></div>
            <div><dt class="text-xs text-slate-500">Margin</dt><dd class="font-semibold text-slate-700">{{ number_format($month['margin'], 1, ',', '.') }}%</dd></div>
        </dl>
        <div class="border-t border-slate-100 px-5 py-4">
            <p class="mb-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">Metode pembayaran</p>
            @php $maxPay = $payments->max('total'); @endphp
            <ul class="space-y-3">
                @foreach ($payments as $pay)
                    <li>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="text-slate-700">{{ $pay['label'] }} <span class="text-xs text-slate-400">· {{ $pay['transaksi'] }} trx</span></span>
                            <span class="font-semibold tabular-nums text-slate-900">{{ rupiah($pay['total']) }}</span>
                        </div>
                        <x-meter :value="$pay['total']" :max="$maxPay" />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Produk terlaris</h2>
                <p class="text-xs text-slate-500">30 hari terakhir, berdasarkan jumlah terjual</p>
            </div>
        </div>
        @if ($topProducts->isEmpty())
            <x-empty icon="chart" message="Belum ada penjualan." />
        @else
            @php $maxQty = $topProducts->max('qty'); @endphp
            <ol class="space-y-3 px-5 py-4">
                @foreach ($topProducts as $i => $row)
                    <li>
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span class="truncate text-slate-700"><span class="mr-1 font-semibold text-slate-400">{{ $i + 1 }}.</span>{{ $row->nama_produk }}</span>
                            <span class="font-semibold whitespace-nowrap tabular-nums text-slate-900">{{ number_format($row->qty, 0, ',', '.') }}</span>
                        </div>
                        <x-meter :value="$row->qty" :max="$maxQty" />
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Peringatan stok</h2>
            <a href="{{ route('pemilik.laporan.stok', ['status' => 'menipis']) }}" class="text-sm font-medium text-brand-600 hover:underline">{{ $restockTotal }} produk</a>
        </div>
        @forelse ($restockProducts as $product)
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-800">{{ $product->nama_produk }}</p>
                    <p class="text-xs text-slate-500">Sisa {{ $product->stok }} · min. {{ $product->stok_minimum }}</p>
                </div>
                <x-stock-badge :product="$product" />
            </div>
        @empty
            <x-empty icon="check-circle" message="Semua stok aman." />
        @endforelse
    </div>

    <div class="space-y-6 lg:col-span-2 xl:col-span-1">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Kasir bertugas</h2></div>
            @forelse ($activeShifts as $shift)
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                    <div class="flex items-center gap-3">
                        <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span></span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $shift->user->nama }}</p>
                            <p class="text-xs text-slate-500">Sejak {{ $shift->waktu_buka->format('H:i') }} · {{ $shift->transactions_count }} transaksi</p>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ rupiah($shift->transactions_sum_total) }}</span>
                </div>
            @empty
                <x-empty icon="user" message="Tidak ada shift yang sedang berjalan." />
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Transaksi terbaru</h2></div>
            @forelse ($recentTransactions as $trx)
                <a href="{{ route('struk.show', $trx) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-2.5 last:border-0 hover:bg-slate-50">
                    <div>
                        <p class="font-mono text-xs font-semibold text-slate-700">{{ $trx->kode_transaksi }}</p>
                        <p class="text-xs text-slate-500">{{ $trx->waktu->diffForHumans() }} · {{ $trx->user->nama }} · {{ $trx->metodeLabel() }}</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ rupiah($trx->total) }}</span>
                </a>
            @empty
                <x-empty icon="receipt" message="Belum ada transaksi." />
            @endforelse
        </div>
    </div>
</div>
@endsection
