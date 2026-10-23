@props(['current', 'previous', 'label' => 'vs kemarin'])
@php
    $diff = $previous > 0 ? ($current - $previous) / $previous * 100 : null;
@endphp
@if ($diff === null)
    <span class="text-slate-400">Belum ada data {{ str_replace('vs ', '', $label) }}</span>
@else
    <span class="inline-flex items-center gap-1 font-medium {{ $diff >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
        <x-icon :name="$diff >= 0 ? 'trend-up' : 'trend-down'" class="size-3.5" />
        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 1, ',', '.') }}%
    </span>
    <span class="text-slate-500">{{ $label }}</span>
@endif
