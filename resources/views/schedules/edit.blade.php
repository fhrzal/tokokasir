@extends('layouts.app')
@section('title', 'Ubah Jadwal')

@php $started = (bool) $schedule->shift; @endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header title="Ubah Jadwal" :subtitle="$schedule->user->nama . ' · ' . $schedule->longLabel()">
        <a href="{{ route('jadwal.index', ['minggu' => $schedule->waktu_mulai->toDateString()]) }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
    </x-page-header>

    @if ($started)
        <div class="mb-5 flex items-start gap-3 rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset">
            <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-slate-500" />
            <p>{{ $schedule->user->nama }} sedang bertugas sejak {{ $schedule->shift->waktu_buka->format('H:i') }}.
                Ubah jam selesai bila shift perlu diperpanjang atau kasir pulang lebih awal; shift dapat ditutup
                mulai {{ \App\Models\ShiftSchedule::tolerance() }} menit sebelum jam selesai.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('jadwal.update', $schedule) }}" class="card">
        @csrf @method('PUT')
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="user_id" class="label">Kasir</label>
                <select id="user_id" name="user_id" required class="input @error('user_id') input-error @enderror" @disabled($started)>
                    @unless ($cashiers->contains('user_id', $schedule->user_id))
                        <option value="{{ $schedule->user_id }}" selected>{{ $schedule->user->nama }} (nonaktif)</option>
                    @endunless
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->user_id }}" @selected((int) old('user_id', $schedule->user_id) === $cashier->user_id)>{{ $cashier->nama }}</option>
                    @endforeach
                </select>
                <x-field-error name="user_id" />
            </div>
            <div class="sm:col-span-2">
                <label for="tanggal" class="label">Tanggal mulai</label>
                <input id="tanggal" type="date" name="tanggal" required value="{{ old('tanggal', $schedule->waktu_mulai->toDateString()) }}" class="input @error('tanggal') input-error @enderror">
                <x-field-error name="tanggal" />
            </div>
            <div>
                <label for="jam_mulai" class="label">Jam mulai</label>
                <input id="jam_mulai" type="time" name="jam_mulai" required value="{{ old('jam_mulai', $schedule->waktu_mulai->format('H:i')) }}" class="input @error('jam_mulai') input-error @enderror">
                <x-field-error name="jam_mulai" />
            </div>
            <div>
                <label for="jam_selesai" class="label">Jam selesai</label>
                <input id="jam_selesai" type="time" name="jam_selesai" required value="{{ old('jam_selesai', $schedule->waktu_selesai->format('H:i')) }}" class="input @error('jam_selesai') input-error @enderror">
                <x-field-error name="jam_selesai" />
            </div>
            <p class="hint -mt-3 sm:col-span-2">Jam selesai lebih awal dari jam mulai berarti shift berakhir keesokan harinya.</p>
            <div class="sm:col-span-2">
                <label for="catatan" class="label">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                <input id="catatan" name="catatan" value="{{ old('catatan', $schedule->catatan) }}" maxlength="100" class="input">
                <x-field-error name="catatan" />
            </div>
        </div>
        <div class="flex justify-end gap-3 rounded-b-xl bg-slate-50 px-6 py-4">
            <a href="{{ route('jadwal.index', ['minggu' => $schedule->waktu_mulai->toDateString()]) }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" />Simpan</button>
        </div>
    </form>
</div>
@endsection
