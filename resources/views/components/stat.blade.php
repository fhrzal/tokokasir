@props(['label', 'value', 'icon' => null, 'hint' => null, 'tone' => 'brand'])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        @if ($icon)
            <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif
    </div>
    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($hint || isset($footer))
        <div class="mt-1 text-xs text-slate-500">{{ $footer ?? $hint }}</div>
    @endif
</div>
