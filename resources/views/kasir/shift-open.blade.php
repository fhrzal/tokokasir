@extends('layouts.app')
@section('title', 'Buka Shift')

@section('content')
<div class="mx-auto max-w-lg py-6">
    <div class="card overflow-hidden">
        <div class="bg-slate-900 px-6 py-6 text-white">
            <p class="text-sm text-slate-300">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="mt-1 text-2xl font-bold">Halo, {{ auth()->user()->nama }}</h1>
            <p class="mt-1 text-sm text-slate-300">Hitung uang tunai di laci kasir lalu catat sebagai kas awal untuk membuka shift.</p>
        </div>

        <form method="POST" action="{{ route('kasir.shift.store') }}" class="space-y-5 p-6"
              x-data="{ kas: '{{ old('kas_awal') ? number_format((float) old('kas_awal'), 0, ',', '.') : '' }}' }">
            @csrf
            <div>
                <label for="kas_awal_display" class="label">Kas awal (uang tunai di laci)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-lg font-semibold text-slate-400">Rp</span>
                    <input id="kas_awal_display" type="text" inputmode="numeric" autocomplete="off" autofocus required
                           x-model="kas" @input="kas = $parseNumber($event.target.value) ? $parseNumber($event.target.value).toLocaleString('id-ID') : ''"
                           class="input py-3 pl-12 text-2xl font-bold tabular-nums @error('kas_awal') input-error @enderror" placeholder="0">
                    <input type="hidden" name="kas_awal" :value="$parseNumber(kas)">
                </div>
                <x-field-error name="kas_awal" />
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ([0, 100000, 200000, 300000, 500000] as $nominal)
                        <button type="button" class="btn btn-secondary btn-sm"
                                @click="kas = '{{ $nominal ? number_format($nominal, 0, ',', '.') : '0' }}'">{{ rupiah($nominal) }}</button>
                    @endforeach
                </div>
            </div>

            @if ($lastShift)
                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
                    <p class="font-medium text-slate-700">Shift terakhir Anda</p>
                    <p class="mt-1">{{ $lastShift->waktu_buka->translatedFormat('d M Y, H:i') }} – {{ $lastShift->waktu_tutup?->format('H:i') }}
                        · Kas akhir {{ rupiah($lastShift->kas_akhir) }}</p>
                </div>
            @endif

            <button type="submit" class="btn btn-primary btn-lg w-full"><x-icon name="power" class="size-5" />Buka Shift &amp; Mulai Transaksi</button>
        </form>
    </div>
</div>
@endsection
