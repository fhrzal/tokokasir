@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
<x-page-header title="Dashboard Admin" :subtitle="now()->translatedFormat('l, d F Y')">
    <a href="{{ route('admin.barang-masuk.create') }}" class="btn btn-primary"><x-icon name="inbox" class="size-4" />Catat Barang Masuk</a>
    <a href="{{ route('admin.produk.create') }}" class="btn btn-secondary"><x-icon name="plus" class="size-4" />Produk Baru</a>
</x-page-header>

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat label="Produk aktif" :value="number_format($stats['produk_aktif'], 0, ',', '.')" icon="cube" />
    <x-stat label="Stok menipis" :value="$stats['stok_menipis']" icon="alert" tone="amber" hint="Mencapai batas stok minimum" />
    <x-stat label="Stok habis" :value="$stats['stok_habis']" icon="x-circle" tone="red" />
    <x-stat label="Supplier" :value="$stats['supplier']" icon="truck" tone="slate" />
</div>

<div class="grid gap-6 xl:grid-cols-5">
    <div class="card xl:col-span-3">
        <div class="card-header">
            <h2 class="card-title">Produk perlu restock</h2>
            <a href="{{ route('admin.produk.index', ['stok' => 'menipis']) }}" class="text-sm font-medium text-brand-600 hover:underline">Lihat semua</a>
        </div>
        @if ($restockProducts->isEmpty())
            <x-empty icon="check-circle" message="Semua stok produk aman." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Produk</th><th class="num">Stok</th><th class="num">Minimum</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($restockProducts as $product)
                        <tr>
                            <td>
                                <p class="font-medium text-slate-800">{{ $product->nama_produk }}</p>
                                <p class="text-xs text-slate-500">{{ $product->category->nama_kategori }}</p>
                            </td>
                            <td class="num font-semibold">{{ $product->stok }}</td>
                            <td class="num text-slate-500">{{ $product->stok_minimum }}</td>
                            <td><x-stock-badge :product="$product" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="space-y-6 xl:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Barang masuk terbaru</h2>
                <a href="{{ route('admin.barang-masuk.index') }}" class="text-sm font-medium text-brand-600 hover:underline">Riwayat</a>
            </div>
            @forelse ($recentPurchases as $purchase)
                <a href="{{ route('admin.barang-masuk.show', $purchase) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $purchase->supplier->nama_supplier }}</p>
                        <p class="text-xs text-slate-500">{{ $purchase->kode() }} · {{ $purchase->tanggal->translatedFormat('d M Y') }} · {{ $purchase->items_count }} produk</p>
                    </div>
                    <span class="text-sm font-semibold whitespace-nowrap text-slate-900">{{ rupiah($purchase->total) }}</span>
                </a>
            @empty
                <x-empty message="Belum ada barang masuk." />
            @endforelse
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Aktivitas stok terbaru</h2>
                <a href="{{ route('admin.stok.index') }}" class="text-sm font-medium text-brand-600 hover:underline">Kartu stok</a>
            </div>
            @forelse ($recentMovements as $movement)
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $movement->product->nama_produk }}</p>
                        <p class="text-xs text-slate-500">{{ $movement->jenisLabel() }} · {{ $movement->waktu->diffForHumans() }} · {{ $movement->user->nama }}</p>
                    </div>
                    <span class="text-sm font-bold tabular-nums {{ $movement->jumlah > 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $movement->jumlah > 0 ? '+' : '' }}{{ $movement->jumlah }}</span>
                </div>
            @empty
                <x-empty message="Belum ada aktivitas stok." />
            @endforelse
        </div>
    </div>
</div>
@endsection
