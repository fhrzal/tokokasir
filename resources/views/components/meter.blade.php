@props(['value', 'max'])
{{-- Batang horizontal tipis untuk perbandingan nilai (satu warna seri). --}}
<div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
    <div class="h-full rounded-full bg-brand-500" style="width: {{ $max > 0 ? max(2, round($value / $max * 100, 1)) : 0 }}%"></div>
</div>
