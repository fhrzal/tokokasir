@extends('layouts.app')
@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="$product->exists ? 'Ubah Produk' : 'Tambah Produk'"
                   :subtitle="$product->exists ? $product->nama_produk : 'Lengkapi data produk yang akan dijual.'">
        <a href="{{ route('admin.produk.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
    </x-page-header>

    <form method="POST" action="{{ $product->exists ? route('admin.produk.update', $product) : route('admin.produk.store') }}"
          class="card divide-y divide-slate-100"
          x-data="{ beli: {{ (float) old('harga_beli', $product->harga_beli ?? 0) }}, jual: {{ (float) old('harga_jual', $product->harga_jual ?? 0) }} }">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div>
                <label for="kode_produk" class="label">Kode produk / barcode</label>
                <div class="relative">
                    <x-icon name="barcode" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input id="kode_produk" name="kode_produk" value="{{ old('kode_produk', $product->kode_produk) }}" required maxlength="30"
                           class="input pl-9 font-mono @error('kode_produk') input-error @enderror" placeholder="Scan atau ketik kode"
                           @keydown.enter.prevent="$refs.nama.focus()">
                </div>
                <p class="hint">Arahkan barcode scanner ke kolom ini untuk mengisi otomatis.</p>
                <x-field-error name="kode_produk" />
            </div>
            <div>
                <label for="category_id" class="label">Kategori</label>
                <select id="category_id" name="category_id" required class="input @error('category_id') input-error @enderror">
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->category_id }}" @selected(old('category_id', $product->category_id) == $category->category_id)>{{ $category->nama_kategori }}</option>
                    @endforeach
                </select>
                <x-field-error name="category_id" />
                @if ($categories->isEmpty())
                    <p class="hint"><a href="{{ route('admin.kategori.index') }}" class="text-brand-600 underline">Buat kategori</a> terlebih dahulu.</p>
                @endif
            </div>
            <div class="sm:col-span-2">
                <label for="nama_produk" class="label">Nama produk</label>
                <input id="nama_produk" x-ref="nama" name="nama_produk" value="{{ old('nama_produk', $product->nama_produk) }}" required maxlength="150"
                       class="input @error('nama_produk') input-error @enderror" placeholder="Mis. Indomie Goreng 85g">
                <x-field-error name="nama_produk" />
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div>
                <label for="harga_beli" class="label">Harga beli</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                    <input id="harga_beli" type="number" name="harga_beli" x-model.number="beli" min="0" step="1" required
                           class="input pl-10 tabular-nums @error('harga_beli') input-error @enderror">
                </div>
                <x-field-error name="harga_beli" />
            </div>
            <div>
                <label for="harga_jual" class="label">Harga jual</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                    <input id="harga_jual" type="number" name="harga_jual" x-model.number="jual" min="0" step="1" required
                           class="input pl-10 tabular-nums @error('harga_jual') input-error @enderror">
                </div>
                <x-field-error name="harga_jual" />
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200 sm:col-span-2" x-show="jual > 0">
                <span class="text-slate-500">Margin per item:</span>
                <span class="font-semibold" :class="jual - beli < 0 ? 'text-red-600' : 'text-slate-900'"
                      x-text="$rupiah(jual - beli) + (jual > 0 ? ' (' + ((jual - beli) / jual * 100).toFixed(1).replace('.', ',') + '%)' : '')"></span>
                <span x-show="jual - beli < 0" class="ml-2 text-xs text-red-600">Harga jual di bawah harga beli.</span>
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:grid-cols-2">
            @if ($product->exists)
                <div>
                    <p class="label">Stok saat ini</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $product->stok }}</p>
                    <p class="hint">Stok diubah melalui <a href="{{ route('admin.barang-masuk.create') }}" class="text-brand-600 underline">Barang Masuk</a> atau <a href="{{ route('admin.stok.index') }}" class="text-brand-600 underline">Penyesuaian Stok</a> agar riwayatnya tercatat.</p>
                </div>
            @else
                <div>
                    <label for="stok" class="label">Stok awal</label>
                    <input id="stok" type="number" name="stok" value="{{ old('stok', 0) }}" min="0" step="1" class="input tabular-nums @error('stok') input-error @enderror">
                    <p class="hint">Dicatat sebagai pergerakan stok "Stok Awal".</p>
                    <x-field-error name="stok" />
                </div>
            @endif
            <div>
                <label for="stok_minimum" class="label">Batas stok minimum</label>
                <input id="stok_minimum" type="number" name="stok_minimum" value="{{ old('stok_minimum', $product->stok_minimum) }}" min="0" step="1" required
                       class="input tabular-nums @error('stok_minimum') input-error @enderror">
                <p class="hint">Peringatan muncul saat stok mencapai angka ini.</p>
                <x-field-error name="stok_minimum" />
            </div>
            <label class="flex items-start gap-3 sm:col-span-2">
                <input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $product->status_aktif)) class="mt-0.5 size-4 rounded border-slate-300">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Produk aktif</span>
                    <span class="block text-xs text-slate-500">Produk nonaktif tidak tampil di halaman kasir.</span>
                </span>
            </label>
        </div>

        <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.produk.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" />Simpan Produk</button>
        </div>
    </form>
</div>
@endsection
