@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<div class="mx-auto max-w-6xl">
    <x-page-header title="Riwayat Transaksi Shift Ini"
                   :subtitle="'Shift dibuka ' . $shift->waktu_buka->translatedFormat('l, d F Y H:i')">
        <a href="{{ route('kasir.pos') }}" class="btn btn-primary"><x-icon name="cart" class="size-4" />Transaksi Baru</a>
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Jumlah transaksi" :value="number_format($summary['jumlah_transaksi'], 0, ',', '.')" icon="receipt" />
        <x-stat label="Total penjualan" :value="rupiah($summary['omzet'])" icon="banknotes" tone="green" />
        <x-stat label="Penjualan tunai" :value="rupiah($summary['tunai'])" icon="banknotes" tone="slate" />
        <x-stat label="Transfer + QRIS" :value="rupiah($summary['per_metode']['transfer'] + $summary['per_metode']['qris'])" icon="qr" tone="violet" />
    </div>

    <div class="card">
        @if ($transactions->isEmpty())
            <x-empty icon="receipt" message="Belum ada transaksi pada shift ini." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>No. Transaksi</th><th>Waktu</th><th class="num">Item</th><th>Metode</th><th class="num">Total</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($transactions as $trx)
                        <tr>
                            <td class="font-mono text-xs font-semibold text-slate-700">{{ $trx->kode_transaksi }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $trx->waktu->format('H:i:s') }}</td>
                            <td class="num">{{ $trx->items_count }}</td>
                            <td><span class="badge {{ $trx->metode_bayar === 'tunai' ? 'badge-green' : ($trx->metode_bayar === 'qris' ? 'badge-violet' : 'badge-blue') }}">{{ $trx->metodeLabel() }}</span></td>
                            <td class="num font-semibold">{{ rupiah($trx->total) }}</td>
                            <td class="text-right"><a href="{{ route('struk.show', $trx) }}" class="btn btn-secondary btn-sm"><x-icon name="printer" class="size-4" />Struk</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
