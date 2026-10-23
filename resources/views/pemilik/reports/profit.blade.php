@extends('layouts.app')
@section('title', 'Laba & Aktivitas Kasir')

@section('content')
<x-page-header title="Laba & Aktivitas Kasir" :subtitle="'Periode ' . $range->label()">
    <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" class="size-4" />Cetak</button>
</x-page-header>

<x-date-filter :range="$range" :action="route('pemilik.laporan.laba')">
    <div>
        <label for="kasir" class="label">Kasir (riwayat shift)</label>
        <select id="kasir" name="kasir" class="input w-44">
            <option value="">Semua kasir</option>
            @foreach ($cashierOptions as $cashier)
                <option value="{{ $cashier->user_id }}" @selected(request('kasir') == $cashier->user_id)>{{ $cashier->nama }}</option>
            @endforeach
        </select>
    </div>
</x-date-filter>

<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat label="Omzet bersih" :value="rupiah($profit['omzet'])" icon="banknotes" :hint="'Penjualan ' . rupiah($profit['penjualan']) . ' − diskon ' . rupiah($profit['diskon'])" />
    <x-stat label="Harga pokok (HPP)" :value="rupiah($profit['hpp'])" icon="archive" tone="slate" hint="Berdasarkan harga beli saat transaksi" />
    <x-stat label="Estimasi laba kotor" :value="rupiah($profit['laba'])" icon="profit" tone="green" />
    <x-stat label="Margin laba kotor" :value="number_format($profit['margin'], 1, ',', '.') . '%'" icon="trend-up" tone="violet" />
</div>

@if ($daily->count() > 1)
    <div class="card mb-6 p-5">
        <h2 class="card-title">Laba kotor harian</h2>
        <x-rupiah-chart :labels="$daily->map(fn ($d) => $d['tanggal']->translatedFormat('d M'))->all()"
                        :series="[['label' => 'Laba kotor', 'data' => $daily->pluck('laba')->all(), 'type' => 'bar']]" />
    </div>
@endif

<div class="card mb-6">
    <div class="card-header"><h2 class="card-title">Aktivitas per kasir</h2></div>
    @if ($cashiers->isEmpty())
        <x-empty icon="users" message="Belum ada akun kasir." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kasir</th><th class="num">Shift</th><th class="num">Jam kerja</th><th class="num">Transaksi</th><th class="num">Omzet</th><th class="num">Kas kurang</th><th class="num">Kas lebih</th></tr></thead>
                <tbody>
                @foreach ($cashiers as $row)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $row['user']->nama }} @unless ($row['user']->status)<span class="badge badge-gray ml-1">Nonaktif</span>@endunless</td>
                        <td class="num">{{ $row['shift'] }}</td>
                        <td class="num">{{ number_format($row['jam_kerja'], 1, ',', '.') }} jam</td>
                        <td class="num">{{ number_format($row['transaksi'], 0, ',', '.') }}</td>
                        <td class="num font-semibold">{{ rupiah($row['omzet']) }}</td>
                        <td class="num {{ $row['kurang'] < 0 ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $row['kurang'] < 0 ? rupiah($row['kurang']) : '–' }}</td>
                        <td class="num {{ $row['lebih'] > 0 ? 'font-semibold text-slate-700' : 'text-slate-400' }}">{{ $row['lebih'] > 0 ? '+' . rupiah($row['lebih']) : '–' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Riwayat shift &amp; selisih kas</h2><span class="text-xs text-slate-500">Kas seharusnya = kas awal + penjualan tunai</span></div>
    @if ($shifts->isEmpty())
        <x-empty icon="clock" message="Tidak ada shift pada periode ini." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kasir</th><th>Waktu shift</th><th class="num">Transaksi</th><th class="num">Penjualan</th><th class="num">Kas awal</th><th class="num">Kas seharusnya</th><th class="num">Kas akhir</th><th>Selisih</th></tr></thead>
                <tbody>
                @foreach ($shifts as $shift)
                    @php $expected = $shift->kas_seharusnya ?? ($shift->kas_awal + ($shift->tunai_sum ?? 0)); @endphp
                    <tr>
                        <td class="font-medium text-slate-800">{{ $shift->user->nama }}</td>
                        <td class="whitespace-nowrap text-slate-600">
                            {{ $shift->waktu_buka->translatedFormat('d M Y') }}
                            <span class="block text-xs">{{ $shift->waktu_buka->format('H:i') }} – {{ $shift->waktu_tutup?->format('H:i') ?? 'sekarang' }}
                                ({{ $shift->waktu_buka->diff($shift->waktu_tutup ?? now())->format('%hj %im') }})</span>
                        </td>
                        <td class="num">{{ $shift->transactions_count }}</td>
                        <td class="num">{{ rupiah($shift->transactions_sum_total) }}</td>
                        <td class="num text-slate-600">{{ rupiah($shift->kas_awal) }}</td>
                        <td class="num text-slate-600">{{ rupiah($expected) }}</td>
                        <td class="num font-semibold">{{ $shift->isOpen() ? '–' : rupiah($shift->kas_akhir) }}</td>
                        <td>
                            @if ($shift->isOpen())
                                <span class="badge badge-blue"><x-icon name="clock" class="size-3.5" />Berjalan</span>
                            @elseif ((float) $shift->selisih == 0)
                                <span class="badge badge-green"><x-icon name="check-circle" class="size-3.5" />Sesuai</span>
                            @elseif ($shift->selisih < 0)
                                <span class="badge badge-red" title="{{ $shift->catatan }}"><x-icon name="alert" class="size-3.5" />Kurang {{ rupiah(abs($shift->selisih)) }}</span>
                            @else
                                <span class="badge badge-yellow" title="{{ $shift->catatan }}"><x-icon name="alert" class="size-3.5" />Lebih {{ rupiah($shift->selisih) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="no-print border-t border-slate-100 px-4 py-3">{{ $shifts->links() }}</div>
    @endif
</div>
@endsection
