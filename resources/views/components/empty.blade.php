@props(['icon' => 'inbox', 'message' => 'Belum ada data.'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="text-sm text-slate-500">{{ $message }}</p>
    {{ $slot }}
</div>
