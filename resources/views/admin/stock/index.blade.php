@extends('layouts.app')
@section('title', 'Stok & Penyesuaian')

@section('content')
<x-page-header title="Stok & Penyesuaian" subtitle="Sesuaikan stok saat ada selisih, barang rusak, atau hasil stok opname." />

<div class="grid gap-6 xl:grid-cols-3">
    {{-- Form penyesuaian (UC-09) --}}
    <form method="POST" action="{{ route('admin.stok.adjust') }}" class="card h-fit space-y-4 p-5"
          x-data="{ mode: '{{ old('mode', 'fisik') }}', productId: '{{ old('product_id') }}', nilai: '{{ old('nilai') }}', stocks: @js($products->pluck('stok', 'product_id')) }">
        @csrf
        <h2 class="card-title">Penyesuaian stok</h2>
        <div>
            <label for="product_id" class="label">Produk</label>
            <select id="product_id" name="product_id" x-model="productId" required class="input @error('product_id') input-error @enderror">
                <option value="">Pilih produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}">{{ $product->nama_produk }} ({{ $product->kode_produk }})</option>
                @endforeach
            </select>
            <p class="hint" x-show="productId" x-cloak>Stok tercatat saat ini: <span class="font-semibold text-slate-800" x-text="stocks[productId]"></span></p>
            <x-field-error name="product_id" />
        </div>
        <div>
            <p class="label">Cara penyesuaian</p>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2.5 text-sm ring-1" :class="mode === 'fisik' ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-slate-300'">
                    <input type="radio" name="mode" value="fisik" x-model="mode" class="sr-only"> Set stok fisik
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2.5 text-sm ring-1" :class="mode === 'selisih' ? 'bg-brand-50 ring-2 ring-brand-500' : 'ring-slate-300'">
                    <input type="radio" name="mode" value="selisih" x-model="mode" class="sr-only"> Tambah / kurangi
                </label>
            </div>
        </div>
        <div>
            <label for="nilai" class="label" x-text="mode === 'fisik' ? 'Jumlah stok fisik (hasil hitung)' : 'Jumlah penyesuaian (+ / −)'"></label>
            <input id="nilai" type="number" name="nilai" x-model="nilai" step="1" required class="input tabular-nums @error('nilai') input-error @enderror"
                   :placeholder="mode === 'fisik' ? 'Mis. 24' : 'Mis. -3 untuk barang rusak'">
            <p class="hint" x-show="productId && nilai !== ''" x-cloak>
                Stok akan menjadi <span class="font-semibold text-slate-800" x-text="mode === 'fisik' ? Number(nilai) : Number(stocks[productId]) + Number(nilai)"></span>
                (<span x-text="(() => { const d = mode === 'fisik' ? Number(nilai) - Number(stocks[productId]) : Number(nilai); return (d > 0 ? '+' : '') + d; })()"></span>)
            </p>
            <x-field-error name="nilai" />
        </div>
        <div>
            <label for="alasan" class="label">Alasan</label>
            <select id="alasan" name="alasan" required class="input">
                @foreach ($alasan as $item)
                    <option value="{{ $item }}" @selected(old('alasan') === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="keterangan" class="label">Keterangan <span class="font-normal text-slate-400">(opsional)</span></label>
            <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" maxlength="100" class="input">
        </div>
        <button type="submit" class="btn btn-primary w-full"><x-icon name="check" class="size-4" />Simpan Penyesuaian</button>
    </form>

    {{-- Kartu stok --}}
    <div class="xl:col-span-2">
        <x-date-filter :range="$range" :action="route('admin.stok.index')">
            <div>
                <label for="f-produk" class="label">Produk</label>
                <select id="f-produk" name="produk" class="input w-52">
                    <option value="">Semua produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->product_id }}" @selected(request('produk') == $product->product_id)>{{ $product->nama_produk }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-jenis" class="label">Jenis</label>
                <select id="f-jenis" name="jenis" class="input w-40">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\StockMovement::JENIS as $key => $label)
                        <option value="{{ $key }}" @selected(request('jenis') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-date-filter>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Riwayat pergerakan stok</h2></div>
            @if ($movements->isEmpty())
                <x-empty icon="adjustments" message="Tidak ada pergerakan stok pada filter ini." />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Waktu</th><th>Produk</th><th>Jenis</th><th class="num">Jumlah</th><th class="num">Stok</th><th>Keterangan</th></tr></thead>
                        <tbody>
                        @foreach ($movements as $movement)
                            @php
                                $badge = ['awal' => 'badge-gray', 'masuk' => 'badge-green', 'penjualan' => 'badge-blue', 'penyesuaian' => 'badge-yellow'][$movement->jenis] ?? 'badge-gray';
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap text-slate-600">{{ $movement->waktu->format('d/m/Y H:i') }}</td>
                                <td class="font-medium text-slate-800">{{ $movement->product->nama_produk }}</td>
                                <td><span class="badge {{ $badge }}">{{ $movement->jenisLabel() }}</span></td>
                                <td class="num font-bold {{ $movement->jumlah > 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $movement->jumlah > 0 ? '+' : '' }}{{ $movement->jumlah }}</td>
                                <td class="num text-slate-600">{{ $movement->stok_sebelum }} → <span class="font-semibold text-slate-900">{{ $movement->stok_sesudah }}</span></td>
                                <td class="text-xs text-slate-600">
                                    {{ $movement->keterangan ?? '' }}
                                    @if ($movement->referensi)<span class="font-mono">{{ $movement->referensi }}</span>@endif
                                    <span class="block text-slate-400">{{ $movement->user->nama }}</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
