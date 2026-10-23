@extends('layouts.app')
@section('title', 'Catat Barang Masuk')

@php
    $oldItems = collect(old('items', []))->values()->map(fn ($row, $i) => [
        'key' => 'old'.$i,
        'product_id' => $row['product_id'] ?? '',
        'qty' => $row['qty'] ?? 1,
        'harga_beli' => $row['harga_beli'] ?? '',
    ]);
@endphp

@section('content')
<x-page-header title="Catat Barang Masuk" subtitle="Stok produk bertambah otomatis setelah disimpan.">
    <a href="{{ route('admin.barang-masuk.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
</x-page-header>

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.barang-masuk.store') }}"
      x-data="purchaseForm(@js(['products' => $products, 'old' => $oldItems]))" class="space-y-6">
    @csrf

    <div class="card grid gap-5 p-6 md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="supplier_id" class="label">Supplier</label>
            <select id="supplier_id" name="supplier_id" required class="input">
                <option value="">Pilih supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->nama_supplier }}</option>
                @endforeach
            </select>
            @if ($suppliers->isEmpty())
                <p class="hint"><a href="{{ route('admin.supplier.create') }}" class="text-brand-600 underline">Tambahkan supplier</a> terlebih dahulu.</p>
            @endif
        </div>
        <div>
            <label for="tanggal" class="label">Tanggal terima</label>
            <input id="tanggal" type="datetime-local" name="tanggal" required value="{{ old('tanggal', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="input">
        </div>
        <div>
            <label for="no_faktur" class="label">No. faktur / nota</label>
            <input id="no_faktur" name="no_faktur" value="{{ old('no_faktur') }}" maxlength="50" class="input" placeholder="Opsional">
        </div>
        <div class="md:col-span-4">
            <label for="keterangan" class="label">Keterangan</label>
            <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" maxlength="255" class="input" placeholder="Opsional">
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-wrap">
            <h2 class="card-title">Daftar produk diterima</h2>
            <div class="relative w-full sm:w-72">
                <x-icon name="barcode" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input x-model="scan" @keydown.enter.prevent="scanEnter()" type="text" class="input pl-9" placeholder="Scan barcode untuk menambah…">
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th class="min-w-64">Produk</th><th class="w-28">Jumlah</th><th class="w-44">Harga beli / unit</th><th class="num">Subtotal</th><th class="w-10"></th></tr></thead>
                <tbody>
                <template x-for="(row, index) in items" :key="row.key">
                    <tr>
                        <td>
                            <select :name="`items[${index}][product_id]`" x-model="row.product_id" @change="productChanged(row)" required class="input">
                                <option value="">Pilih produk</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="`${p.nama} (${p.kode}) · stok ${p.stok}`" :selected="Number(row.product_id) === p.id"></option>
                                </template>
                            </select>
                        </td>
                        <td><input type="number" :name="`items[${index}][qty]`" x-model.number="row.qty" min="1" step="1" required class="input tabular-nums"></td>
                        <td>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
                                <input type="number" :name="`items[${index}][harga_beli]`" x-model.number="row.harga_beli" min="0" step="1" required class="input pl-10 tabular-nums">
                            </div>
                        </td>
                        <td class="num font-semibold" x-text="rupiah(subtotal(row))"></td>
                        <td><button type="button" @click="removeRow(index)" class="text-slate-400 hover:text-red-600" aria-label="Hapus baris"><x-icon name="trash" class="size-4" /></button></td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 px-5 py-4">
            <button type="button" @click="addRow()" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" />Tambah baris</button>
            <p class="text-sm text-slate-500">Total pembelian <span class="ml-2 text-xl font-bold text-slate-900" x-text="rupiah(total)"></span></p>
        </div>
    </div>

    <div class="flex flex-col-reverse items-stretch justify-between gap-4 sm:flex-row sm:items-center">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="update_harga_beli" value="0">
            <input type="checkbox" name="update_harga_beli" value="1" @checked(old('update_harga_beli', true)) class="size-4 rounded border-slate-300">
            Perbarui harga beli produk dengan harga terbaru
        </label>
        <button type="submit" class="btn btn-primary btn-lg"><x-icon name="check" class="size-5" />Simpan Barang Masuk</button>
    </div>
</form>
@endsection
