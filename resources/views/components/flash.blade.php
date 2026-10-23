@php
    $messages = collect([
        'success' => ['badge' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20', 'icon' => 'check-circle'],
        'error' => ['badge' => 'bg-red-50 text-red-800 ring-red-600/20', 'icon' => 'x-circle'],
        'warning' => ['badge' => 'bg-amber-50 text-amber-900 ring-amber-600/25', 'icon' => 'alert'],
    ])->filter(fn ($style, $key) => session()->has($key));
@endphp

@foreach ($messages as $key => $style)
    <div x-data="{ show: true }" x-show="show" x-transition.opacity
         class="no-print mb-5 flex items-start gap-3 rounded-lg px-4 py-3 text-sm ring-1 ring-inset {{ $style['badge'] }}" role="status">
        <x-icon :name="$style['icon']" class="mt-0.5 size-5 shrink-0" />
        <p class="flex-1">{{ session($key) }}</p>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Tutup">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
@endforeach
