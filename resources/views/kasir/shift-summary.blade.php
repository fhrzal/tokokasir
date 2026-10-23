@extends('layouts.app')
@section('title', 'Ringkasan Shift')

@section('content')
<div class="mx-auto max-w-md">
    <div class="no-print mb-4 flex flex-wrap gap-2">
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak</button>
        @if (! auth()->user()->openShift()->exists())
            <a href="{{ route('kasir.shift.create') }}" class="btn btn-primary"><x-icon name="power" class="size-4" />Buka Shift Baru</a>
        @endif
    </div>

    <div class="print-area card p-6 font-mono text-sm">
        <div class="text-center">
            <p class="text-base font-bold">{{ config('tokokasir.nama') }}</p>
            <p class="text-xs">LAPORAN TUTUP SHIFT</p>
        </div>
        <div class="my-3 border-t border-dashed border-slate-400"></div>
        @php
            $rows = [
                'Kasir' => $shift->user->nama,
                'Buka' => $shift->waktu_buka->format('d/m/Y H:i'),
                'Tutup' => $shift->waktu_tutup?->format('d/m/Y H:i') ?? '-',
            ];
        @endphp
        @foreach ($rows as $label => $value)
            <div class="flex justify-between"><span>{{ $label }}</span><span>{{ $value }}</span></div>
        @endforeach
        <div class="my-3 border-t border-dashed border-slate-400"></div>
        <div class="flex justify-between"><span>Transaksi</span><span>{{ $summary['jumlah_transaksi'] }}</span></div>
        @foreach (config('tokokasir.metode_bayar') as $key => $label)
            <div class="flex justify-between"><span>{{ $label }}</span><span>{{ rupiah($summary['per_metode'][$key], false) }}</span></div>
        @endforeach
        <div class="flex justify-between font-bold"><span>Total Penjualan</span><span>{{ rupiah($summary['omzet'], false) }}</span></div>
        <div class="my-3 border-t border-dashed border-slate-400"></div>
        <div class="flex justify-between"><span>Kas Awal</span><span>{{ rupiah($shift->kas_awal, false) }}</span></div>
        <div class="flex justify-between"><span>Kas Seharusnya</span><span>{{ rupiah($shift->kas_seharusnya ?? $summary['kas_seharusnya'], false) }}</span></div>
        <div class="flex justify-between"><span>Kas Akhir</span><span>{{ rupiah($shift->kas_akhir, false) }}</span></div>
        <div class="flex justify-between font-bold"><span>Selisih</span><span>{{ rupiah($shift->selisih, false) }}</span></div>
        @if ($shift->catatan)
            <div class="my-3 border-t border-dashed border-slate-400"></div>
            <p class="text-xs">Catatan: {{ $shift->catatan }}</p>
        @endif
        <div class="my-3 border-t border-dashed border-slate-400"></div>
        <p class="text-center text-xs">Dicetak {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</div>
@endsection
