@extends('layouts.app')
@section('title', 'Tutup Shift')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header title="Tutup Shift"
                   :subtitle="'Dibuka ' . $shift->waktu_buka->translatedFormat('l, d F Y H:i') . ' · ' . $shift->waktu_buka->diffForHumans(null, true) . ' berjalan'" />

    <div class="grid gap-6 md:grid-cols-5">
        <div class="card md:col-span-2">
            <div class="card-header"><h2 class="card-title">Rekap shift</h2></div>
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex justify-between px-5 py-3"><dt class="text-slate-500">Jumlah transaksi</dt><dd class="font-semibold">{{ $summary['jumlah_transaksi'] }}</dd></div>
                <div class="flex justify-between px-5 py-3"><dt class="text-slate-500">Total penjualan</dt><dd class="font-semibold">{{ rupiah($summary['omzet']) }}</dd></div>
                @foreach (config('tokokasir.metode_bayar') as $key => $label)
                    <div class="flex justify-between px-5 py-2.5 pl-8"><dt class="text-slate-500">{{ $label }}</dt><dd class="tabular-nums">{{ rupiah($summary['per_metode'][$key]) }}</dd></div>
                @endforeach
                <div class="flex justify-between px-5 py-3"><dt class="text-slate-500">Kas awal</dt><dd class="font-semibold">{{ rupiah($shift->kas_awal) }}</dd></div>
                <div class="flex justify-between bg-slate-50 px-5 py-3"><dt class="font-semibold text-slate-700">Kas seharusnya</dt><dd class="font-bold text-slate-900">{{ rupiah($summary['kas_seharusnya']) }}</dd></div>
            </dl>
            <p class="px-5 pb-4 text-xs text-slate-500">Kas seharusnya = kas awal + penjualan tunai.</p>
        </div>

        <form method="POST" action="{{ route('kasir.shift.close') }}" class="card space-y-5 p-6 md:col-span-3"
              x-data="{ kas: '', expected: {{ $summary['kas_seharusnya'] }} }"
              @submit="if (!confirm('Tutup shift sekarang? Anda tidak dapat bertransaksi sampai membuka shift baru.')) $event.preventDefault()">
            @csrf
            <div>
                <label for="kas_akhir_display" class="label">Kas akhir (uang tunai aktual di laci)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-lg font-semibold text-slate-400">Rp</span>
                    <input id="kas_akhir_display" type="text" inputmode="numeric" autocomplete="off" autofocus required x-model="kas"
                           @input="kas = $parseNumber($event.target.value) ? $parseNumber($event.target.value).toLocaleString('id-ID') : ($event.target.value.trim() === '0' ? '0' : '')"
                           class="input py-3 pl-12 text-2xl font-bold tabular-nums @error('kas_akhir') input-error @enderror" placeholder="0">
                    <input type="hidden" name="kas_akhir" :value="$parseNumber(kas)">
                </div>
                <x-field-error name="kas_akhir" />
            </div>

            <div class="rounded-xl p-4 ring-1" x-show="kas !== ''" x-cloak
                 :class="$parseNumber(kas) - expected === 0 ? 'bg-emerald-50 ring-emerald-200' : 'bg-amber-50 ring-amber-200'">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <x-icon name="check-circle" class="size-5 text-emerald-600" x-show="$parseNumber(kas) - expected === 0" />
                        <x-icon name="alert" class="size-5 text-amber-600" x-show="$parseNumber(kas) - expected !== 0" />
                        <span x-text="$parseNumber(kas) - expected === 0 ? 'Kas sesuai' : ($parseNumber(kas) - expected < 0 ? 'Kas kurang' : 'Kas lebih')"></span>
                    </span>
                    <span class="text-xl font-bold tabular-nums text-slate-900" x-text="$rupiah($parseNumber(kas) - expected)"></span>
                </div>
            </div>

            <div>
                <label for="catatan" class="label">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea id="catatan" name="catatan" rows="2" maxlength="255" class="input" placeholder="Mis. alasan selisih kas">{{ old('catatan') }}</textarea>
            </div>

            <button type="submit" class="btn btn-danger btn-lg w-full"><x-icon name="power" class="size-5" />Tutup Shift</button>
        </form>
    </div>
</div>
@endsection
