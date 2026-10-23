@props(['range', 'action'])
{{-- Filter periode laporan: preset atau rentang tanggal. Slot berisi filter tambahan. --}}
<form method="GET" action="{{ $action }}" x-data="{ periode: @js($range->preset) }"
      class="no-print card mb-6 flex flex-wrap items-end gap-3 p-4">
    <div>
        <label class="label" for="periode">Periode</label>
        <select id="periode" name="periode" x-model="periode" class="input w-44"
                @change="if (periode !== 'custom') $el.form.submit()">
            @foreach (\App\Support\DateRange::PRESETS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <template x-if="periode === 'custom'">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label" for="dari">Dari</label>
                <input id="dari" type="date" name="dari" value="{{ $range->start->toDateString() }}" class="input w-40">
            </div>
            <div>
                <label class="label" for="sampai">Sampai</label>
                <input id="sampai" type="date" name="sampai" value="{{ $range->end->toDateString() }}" class="input w-40">
            </div>
        </div>
    </template>
    {{ $slot }}
    <button type="submit" class="btn btn-primary">Terapkan</button>
    <p class="ml-auto self-center text-sm text-slate-500">
        <x-icon name="clock" class="mr-1 inline size-4 align-text-bottom" />{{ $range->label() }}
    </p>
</form>
