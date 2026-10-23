@extends('layouts.app')
@section('title', 'Detail Barang Masuk')

@section('content')
<x-page-header :title="'Barang Masuk ' . $purchase->kode()" :subtitle="$purchase->tanggal->translatedFormat('l, d F Y H:i')">
    <a href="{{ route('admin.barang-masuk.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
    <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak</button>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card h-fit">
        <dl class="divide-y divide-slate-100 text-sm">
            <div class="px-5 py-3"><dt class="text-slate-500">Supplier</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $purchase->supplier->nama_supplier }}</dd></div>
            <div class="px-5 py-3"><dt class="text-slate-500">Telepon</dt><dd class="mt-0.5">{{ $purchase->supplier->telepon ?: '–' }}</dd></div>
            <div class="px-5 py-3"><dt class="text-slate-500">No. faktur</dt><dd class="mt-0.5">{{ $purchase->no_faktur ?: '–' }}</dd></div>
            <div class="px-5 py-3"><dt class="text-slate-500">Dicatat oleh</dt><dd class="mt-0.5">{{ $purchase->user->nama }} · {{ $purchase->created_at->translatedFormat('d M Y H:i') }}</dd></div>
            @if ($purchase->keterangan)
                <div class="px-5 py-3"><dt class="text-slate-500">Keterangan</dt><dd class="mt-0.5">{{ $purchase->keterangan }}</dd></div>
            @endif
        </dl>
    </div>
    <div class="card lg:col-span-2">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Produk</th><th class="num">Jumlah</th><th class="num">Harga beli</th><th class="num">Subtotal</th></tr></thead>
                <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800">{{ $item->product->nama_produk }}</p>
                            <p class="font-mono text-xs text-slate-500">{{ $item->product->kode_produk }}</p>
                        </td>
                        <td class="num">{{ $item->qty }}</td>
                        <td class="num">{{ rupiah($item->harga_beli) }}</td>
                        <td class="num font-semibold">{{ rupiah($item->subtotal) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="bg-slate-50"><td colspan="3" class="px-4 py-3 text-right font-semibold">Total</td><td class="num px-4 py-3 text-lg font-bold">{{ rupiah($purchase->total) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
