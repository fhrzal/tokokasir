@extends('layouts.app')
@section('title', 'Jadwal Kasir')

@php
    $weekEnd = $week->addDays(6);
    $hariOptions = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
    $oldHari = array_map('intval', old('hari', array_keys($hariOptions)));
@endphp

@section('content')
<x-page-header title="Jadwal Kasir"
               :subtitle="'Kasir hanya dapat masuk, membuka, dan menutup shift sesuai jadwal ini (toleransi ' . \App\Models\ShiftSchedule::tolerance() . ' menit).'" />

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Tambah jadwal --}}
    <form method="POST" action="{{ route('jadwal.store') }}" class="card h-fit space-y-4 p-5"
          x-data="{ sampai: @js(old('tanggal_selesai', '')) }">
        @csrf
        <h2 class="card-title">Tambah jadwal</h2>

        @if ($cashiers->isEmpty())
            <p class="text-sm text-slate-500">Belum ada akun kasir yang aktif.</p>
        @else
            <div>
                <label for="user_id" class="label">Kasir</label>
                <select id="user_id" name="user_id" required class="input @error('user_id') input-error @enderror">
                    <option value="">Pilih kasir</option>
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->user_id }}" @selected((int) old('user_id') === $cashier->user_id)>{{ $cashier->nama }}</option>
                    @endforeach
                </select>
                <x-field-error name="user_id" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="tanggal_mulai" class="label">Tanggal</label>
                    <input id="tanggal_mulai" type="date" name="tanggal_mulai" required min="{{ today()->toDateString() }}"
                           value="{{ old('tanggal_mulai', today()->toDateString()) }}" class="input @error('tanggal_mulai') input-error @enderror">
                </div>
                <div>
                    <label for="tanggal_selesai" class="label">Sampai <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="tanggal_selesai" type="date" name="tanggal_selesai" x-model="sampai" min="{{ today()->toDateString() }}"
                           class="input @error('tanggal_selesai') input-error @enderror">
                </div>
            </div>
            <x-field-error name="tanggal_mulai" />
            <x-field-error name="tanggal_selesai" />

            <fieldset x-show="sampai" x-cloak>
                <legend class="label">Hari kerja</legend>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($hariOptions as $value => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="hari[]" value="{{ $value }}" class="peer sr-only" @checked(in_array($value, $oldHari, true)) :disabled="!sampai">
                            <span class="inline-block rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-field-error name="hari" />
            </fieldset>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="jam_mulai" class="label">Jam mulai</label>
                    <input id="jam_mulai" type="time" name="jam_mulai" required value="{{ old('jam_mulai', '07:00') }}" class="input @error('jam_mulai') input-error @enderror">
                </div>
                <div>
                    <label for="jam_selesai" class="label">Jam selesai</label>
                    <input id="jam_selesai" type="time" name="jam_selesai" required value="{{ old('jam_selesai', '15:00') }}" class="input @error('jam_selesai') input-error @enderror">
                </div>
            </div>
            <p class="hint -mt-2">Jam selesai lebih awal dari jam mulai berarti shift berakhir keesokan harinya.</p>
            <x-field-error name="jam_mulai" />
            <x-field-error name="jam_selesai" />

            <div>
                <label for="catatan" class="label">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                <input id="catatan" name="catatan" value="{{ old('catatan') }}" maxlength="100" class="input" placeholder="Mis. menggantikan Budi">
            </div>

            <button type="submit" class="btn btn-primary w-full"><x-icon name="plus" class="size-4" />Simpan Jadwal</button>
        @endif
    </form>

    {{-- Jadwal per minggu --}}
    <div class="card lg:col-span-2">
        <div class="card-header flex flex-wrap items-center justify-between gap-3">
            <h2 class="card-title">{{ $week->translatedFormat('j M') }} – {{ $weekEnd->translatedFormat('j M Y') }}</h2>
            <div class="flex items-center gap-1">
                <a href="{{ route('jadwal.index', ['minggu' => $week->subWeek()->toDateString()]) }}" class="btn btn-ghost btn-sm" aria-label="Minggu sebelumnya"><x-icon name="chevron-left" class="size-4" /></a>
                <a href="{{ route('jadwal.index') }}" class="btn btn-secondary btn-sm">Minggu ini</a>
                <a href="{{ route('jadwal.index', ['minggu' => $week->addWeek()->toDateString()]) }}" class="btn btn-ghost btn-sm" aria-label="Minggu berikutnya"><x-icon name="chevron-right" class="size-4" /></a>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($days as $day)
                @php $items = $schedules->get($day->toDateString(), collect()); @endphp
                <section>
                    <h3 class="flex items-center gap-2 bg-slate-50 px-5 py-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        {{ $day->translatedFormat('l, j F') }}
                        @if ($day->isToday())<span class="badge badge-blue normal-case">Hari ini</span>@endif
                    </h3>
                    @forelse ($items as $schedule)
                        @php [$statusLabel, $statusBadge] = \App\Models\ShiftSchedule::STATUS[$schedule->status()]; @endphp
                        <div class="flex min-h-14 flex-wrap items-center gap-x-4 gap-y-2 px-5 py-2">
                            <div class="w-36 shrink-0">
                                <p class="font-semibold tabular-nums text-slate-900">{{ $schedule->timeLabel() }}</p>
                                @if ($schedule->catatan)<p class="truncate text-xs text-slate-500" title="{{ $schedule->catatan }}">{{ $schedule->catatan }}</p>@endif
                            </div>
                            <p class="min-w-32 flex-1 font-medium text-slate-700">{{ $schedule->user->nama }}</p>
                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                @if ($schedule->shift)
                                    <span class="text-slate-500 tabular-nums">
                                        {{ $schedule->shift->waktu_buka->format('H:i') }}–{{ $schedule->shift->waktu_tutup?->format('H:i') ?? '…' }}
                                    </span>
                                    @if ($late = $schedule->lateMinutes())
                                        <span class="badge badge-yellow">Terlambat {{ $late }} mnt</span>
                                    @endif
                                @endif
                            </div>
                            <div class="ml-auto flex items-center whitespace-nowrap">
                                @if ($schedule->isEditable())
                                    <a href="{{ route('jadwal.edit', $schedule) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Ubah</a>
                                @endif
                                @if ($schedule->isDeletable())
                                    <form method="POST" action="{{ route('jadwal.destroy', $schedule) }}" class="inline"
                                          onsubmit="return confirm(@js('Hapus jadwal ' . $schedule->user->nama . ' ' . $schedule->timeLabel() . '?'))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm text-red-600"><x-icon name="trash" class="size-4" />Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-3 text-sm text-slate-400">Tidak ada jadwal.</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
