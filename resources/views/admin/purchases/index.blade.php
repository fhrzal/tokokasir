@extends('layouts.app')
@section('title', 'Barang Masuk')

@section('content')
<x-page-header title="Barang Masuk" subtitle="Riwayat penerimaan barang dari supplier.">
    <a href="{{ route('admin.barang-masuk.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Catat Barang Masuk</a>
</x-page-header>

<x-date-filter :range="$range" :action="route('admin.barang-masuk.index')">
    <div>
        <label for="supplier" class="label">Supplier</label>
        <select id="supplier" name="supplier" class="input w-52">
            <option value="">Semua supplier</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->supplier_id }}" @selected(request('supplier') == $supplier->supplier_id)>{{ $supplier->nama_supplier }}</option>
            @endforeach
        </select>
    </div>
</x-date-filter>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ $purchases->total() }} pembelian</h2>
        <p class="text-sm text-slate-500">Total nilai: <span class="font-semibold text-slate-900">{{ rupiah($totalNilai) }}</span></p>
    </div>
    @if ($purchases->isEmpty())
        <x-empty icon="inbox" message="Tidak ada barang masuk pada periode ini." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Supplier</th><th>No. faktur</th><th class="num">Produk</th><th class="num">Total</th><th>Dicatat oleh</th><th></th></tr></thead>
                <tbody>
                @foreach ($purchases as $purchase)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $purchase->kode() }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $purchase->tanggal->translatedFormat('d M Y H:i') }}</td>
                        <td class="font-medium text-slate-800">{{ $purchase->supplier->nama_supplier }}</td>
                        <td class="text-slate-600">{{ $purchase->no_faktur ?: '–' }}</td>
                        <td class="num">{{ $purchase->items_count }}</td>
                        <td class="num font-semibold">{{ rupiah($purchase->total) }}</td>
                        <td class="text-slate-600">{{ $purchase->user->nama }}</td>
                        <td class="text-right"><a href="{{ route('admin.barang-masuk.show', $purchase) }}" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" />Detail</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $purchases->links() }}</div>
    @endif
</div>
@endsection
