@extends('layouts.app')
@section('title', 'Laporan Stok')

@section('content')
<x-page-header title="Laporan Stok" subtitle="Kondisi persediaan dan produk terlaris.">
    <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak</button>
</x-page-header>

<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat label="Produk aktif" :value="number_format($totals->produk, 0, ',', '.')" icon="cube" :hint="number_format($totals->unit, 0, ',', '.') . ' unit tersedia'" />
    <x-stat label="Nilai persediaan" :value="rupiah($totals->nilai_beli)" icon="archive" tone="slate" :hint="'Nilai jual ' . rupiah($totals->nilai_jual)" />
    <a href="{{ route('pemilik.laporan.stok', ['status' => 'menipis']) }}" class="block"><x-stat label="Stok menipis" :value="$lowCount" icon="alert" tone="amber" hint="Lihat daftar →" class="h-full hover:ring-amber-300" /></a>
    <a href="{{ route('pemilik.laporan.stok', ['status' => 'habis']) }}" class="block"><x-stat label="Stok habis" :value="$outCount" icon="x-circle" tone="red" hint="Lihat daftar →" class="h-full hover:ring-red-300" /></a>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <form method="GET" class="no-print card mb-4 grid gap-3 p-4 sm:grid-cols-4">
            <input type="hidden" name="periode" value="{{ $range->preset }}">
            <div class="relative sm:col-span-2">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk…" class="input pl-9">
            </div>
            <select name="kategori" class="input" onchange="this.form.submit()">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->category_id }}" @selected(request('kategori') == $category->category_id)>{{ $category->nama_kategori }}</option>
                @endforeach
            </select>
            <select name="status" class="input" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="aman" @selected(request('status') === 'aman')>Aman</option>
                <option value="menipis" @selected(request('status') === 'menipis')>Menipis</option>
                <option value="habis" @selected(request('status') === 'habis')>Habis</option>
            </select>
        </form>

        <div class="card">
            @if ($products->isEmpty())
                <x-empty icon="archive" message="Tidak ada produk pada filter ini." />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Produk</th><th>Kategori</th><th class="num">Stok</th><th class="num">Minimum</th><th class="num">Nilai stok</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $product->nama_produk }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $product->kode_produk }}</p>
                                </td>
                                <td class="text-slate-600">{{ $product->category->nama_kategori }}</td>
                                <td class="num font-semibold">{{ $product->stok }}</td>
                                <td class="num text-slate-500">{{ $product->stok_minimum }}</td>
                                <td class="num text-slate-600">{{ rupiah(max($product->stok, 0) * $product->harga_beli) }}</td>
                                <td><x-stock-badge :product="$product" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="no-print border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>
            @endif
        </div>
    </div>

    <div class="card h-fit">
        <div class="card-header flex-wrap">
            <h2 class="card-title">Produk terlaris</h2>
            <form method="GET" class="no-print">
                @foreach (request()->only('q', 'kategori', 'status') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <select name="periode" class="input py-1 text-xs" onchange="this.form.submit()" aria-label="Periode produk terlaris">
                    @foreach (collect(\App\Support\DateRange::PRESETS)->except('custom') as $key => $label)
                        <option value="{{ $key }}" @selected($range->preset === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        @if ($topProducts->isEmpty())
            <x-empty icon="chart" message="Belum ada penjualan pada periode ini." />
        @else
            @php $maxQty = $topProducts->max('qty'); @endphp
            <ol class="space-y-3.5 px-5 py-4">
                @foreach ($topProducts as $i => $row)
                    <li>
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span class="truncate text-slate-700"><span class="mr-1 font-semibold text-slate-400">{{ $i + 1 }}.</span>{{ $row->nama_produk }}</span>
                            <span class="font-semibold whitespace-nowrap tabular-nums text-slate-900">{{ number_format($row->qty, 0, ',', '.') }} terjual</span>
                        </div>
                        <x-meter :value="$row->qty" :max="$maxQty" />
                        <p class="mt-1 text-xs text-slate-500">{{ rupiah($row->omzet) }} · sisa stok {{ $row->stok }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
@endsection
