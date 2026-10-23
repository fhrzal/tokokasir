@extends('layouts.app')
@section('title', 'Struk ' . $transaction->kode_transaksi)

@section('content')
@php $isKasir = auth()->user()->hasRole('kasir'); @endphp
<div class="mx-auto max-w-lg">
    @if ($isNew)
        <div class="no-print card mb-5 overflow-hidden">
            <div class="flex items-center gap-3 bg-emerald-600 px-5 py-4 text-white">
                <x-icon name="check-circle" class="size-8" />
                <div>
                    <p class="font-bold">Transaksi berhasil</p>
                    <p class="text-sm text-emerald-100">{{ $transaction->kode_transaksi }} · {{ $transaction->metodeLabel() }}</p>
                </div>
            </div>
            @if ($transaction->metode_bayar === 'tunai')
                <div class="flex items-center justify-between px-5 py-4">
                    <span class="text-sm font-medium text-slate-500">Kembalian</span>
                    <span class="text-3xl font-bold text-slate-900">{{ rupiah($transaction->kembalian) }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="no-print mb-4 flex flex-wrap gap-2">
        @if ($isKasir)
            <a href="{{ route('kasir.pos') }}" class="btn btn-primary" @if ($isNew) autofocus @endif><x-icon name="cart" class="size-4" />Transaksi Baru</a>
        @else
            <a href="{{ url()->previous() }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
        @endif
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak Struk</button>
        <a href="{{ route('struk.pdf', $transaction) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" />Simpan PDF</a>
    </div>

    <div class="print-area mx-auto w-full max-w-[80mm] bg-white p-5 font-mono text-xs leading-relaxed text-black shadow-sm ring-1 ring-slate-200">
        @include('receipts._body')
    </div>

    @if ($isKasir)
        <p class="no-print mt-4 text-center text-xs text-slate-500">Tekan <kbd class="rounded bg-white px-1.5 py-0.5 ring-1 ring-slate-300">Enter</kbd> untuk transaksi baru, <kbd class="rounded bg-white px-1.5 py-0.5 ring-1 ring-slate-300">Ctrl</kbd>+<kbd class="rounded bg-white px-1.5 py-0.5 ring-1 ring-slate-300">P</kbd> untuk mencetak.</p>
    @endif
</div>
@endsection
