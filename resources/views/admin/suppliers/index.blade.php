@extends('layouts.app')
@section('title', 'Data Supplier')

@section('content')
<x-page-header title="Data Supplier" subtitle="Pemasok barang yang digunakan saat mencatat barang masuk.">
    <a href="{{ route('admin.supplier.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Tambah Supplier</a>
</x-page-header>

<form method="GET" class="card mb-6 flex gap-3 p-4">
    <div class="relative flex-1">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau telepon supplier…" class="input pl-9">
    </div>
    <button class="btn btn-secondary">Cari</button>
</form>

<div class="card">
    @if ($suppliers->isEmpty())
        <x-empty icon="truck" message="Belum ada supplier." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Supplier</th><th>Telepon</th><th>Alamat</th><th class="num">Pembelian</th><th>Terakhir</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                @foreach ($suppliers as $supplier)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $supplier->nama_supplier }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $supplier->telepon ?: '–' }}</td>
                        <td class="max-w-xs truncate text-slate-600" title="{{ $supplier->alamat }}">{{ $supplier->alamat ?: '–' }}</td>
                        <td class="num">{{ $supplier->purchases_count }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $supplier->purchases_max_tanggal ? \Carbon\Carbon::parse($supplier->purchases_max_tanggal)->translatedFormat('d M Y') : '–' }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.supplier.edit', $supplier) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Ubah</a>
                            @if ($supplier->purchases_count === 0)
                                <form method="POST" action="{{ route('admin.supplier.destroy', $supplier) }}" class="inline" onsubmit="return confirm('Hapus supplier ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-red-600"><x-icon name="trash" class="size-4" />Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $suppliers->links() }}</div>
    @endif
</div>
@endsection
