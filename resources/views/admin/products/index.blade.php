@extends('layouts.app')
@section('title', 'Data Produk')

@section('content')
<x-page-header title="Data Produk" :subtitle="$products->total() . ' produk terdaftar'">
    <a href="{{ route('admin.produk.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Tambah Produk</a>
</x-page-header>

<form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="relative sm:col-span-2">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode/barcode…" class="input pl-9">
    </div>
    <select name="kategori" class="input" onchange="this.form.submit()">
        <option value="">Semua kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->category_id }}" @selected(request('kategori') == $category->category_id)>{{ $category->nama_kategori }}</option>
        @endforeach
    </select>
    <select name="stok" class="input" onchange="this.form.submit()">
        <option value="">Semua stok</option>
        <option value="menipis" @selected(request('stok') === 'menipis')>Stok menipis</option>
        <option value="habis" @selected(request('stok') === 'habis')>Stok habis</option>
    </select>
    <select name="status" class="input" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
    </select>
</form>

<div class="card">
    @if ($products->isEmpty())
        <x-empty icon="cube" message="Produk tidak ditemukan." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Produk</th><th>Kategori</th><th class="num">Harga beli</th><th class="num">Harga jual</th><th class="num">Stok</th><th>Status</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                @foreach ($products as $product)
                    <tr class="{{ $product->status_aktif ? '' : 'opacity-60' }}">
                        <td>
                            <p class="font-medium text-slate-800">{{ $product->nama_produk }}</p>
                            <p class="font-mono text-xs text-slate-500">{{ $product->kode_produk }}</p>
                        </td>
                        <td class="text-slate-600">{{ $product->category->nama_kategori }}</td>
                        <td class="num text-slate-600">{{ rupiah($product->harga_beli) }}</td>
                        <td class="num font-medium">{{ rupiah($product->harga_jual) }}</td>
                        <td class="num">
                            <span class="font-semibold">{{ $product->stok }}</span>
                            <span class="block text-xs text-slate-400">min. {{ $product->stok_minimum }}</span>
                        </td>
                        <td class="space-y-1">
                            <x-stock-badge :product="$product" />
                            @unless ($product->status_aktif)
                                <span class="badge badge-gray">Nonaktif</span>
                            @endunless
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.produk.edit', $product) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Ubah</a>
                            <form method="POST" action="{{ route('admin.produk.toggle', $product) }}" class="inline"
                                  onsubmit="return confirm('{{ $product->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }} produk ini?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-sm {{ $product->status_aktif ? 'text-red-600' : 'text-emerald-700' }}">
                                    <x-icon name="power" class="size-4" />{{ $product->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>
    @endif
</div>
@endsection
