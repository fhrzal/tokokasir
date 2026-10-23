@props(['labels', 'series', 'height' => 'h-72'])
{{-- Grafik rupiah satu sumbu + tampilan tabel sebagai alternatif aksesibel. --}}
<div x-data="{ table: false }">
    <div class="mb-3 flex justify-end">
        <div class="flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
            <button type="button" @click="table = false" class="rounded-md px-2.5 py-1" :class="!table ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Grafik</button>
            <button type="button" @click="table = true" class="rounded-md px-2.5 py-1" :class="table ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Tabel</button>
        </div>
    </div>
    <div x-show="!table" class="relative {{ $height }}" x-data="rupiahChart(@js(['labels' => $labels, 'series' => $series]))">
        <canvas x-ref="canvas" role="img" aria-label="{{ collect($series)->pluck('label')->join(' dan ') }}"></canvas>
    </div>
    <div x-show="table" x-cloak class="table-wrap max-h-72 overflow-y-auto">
        <table class="table">
            <thead><tr><th>Tanggal</th>@foreach ($series as $s)<th class="num">{{ $s['label'] }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($labels as $i => $label)
                <tr><td>{{ $label }}</td>@foreach ($series as $s)<td class="num">{{ rupiah($s['data'][$i] ?? 0) }}</td>@endforeach</tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
