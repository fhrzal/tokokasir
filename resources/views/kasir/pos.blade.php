@extends('layouts.app')
@section('title', 'Transaksi')
@section('main-class', 'p-0')

@php
    $qrisPath = config('tokokasir.qris_image');
    $qrisExists = $qrisPath && file_exists(public_path($qrisPath));
@endphp

@section('content')
<div x-data="pos(@js([
        'products' => $products,
        'storeUrl' => route('kasir.pos.store'),
        'productsUrl' => route('kasir.pos.products'),
        'shiftUrl' => route('kasir.shift.create'),
        'csrf' => csrf_token(),
    ]))"
     class="grid grid-cols-1 md:h-[calc(100vh-3.5rem)] md:grid-cols-[minmax(0,1fr)_340px] lg:grid-cols-[minmax(0,1fr)_400px] xl:grid-cols-[minmax(0,1fr)_440px]">

    {{-- ============ Daftar produk ============ --}}
    <section class="flex min-h-0 min-w-0 flex-col">
        <div class="space-y-3 border-b border-slate-200 bg-white p-4">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" />
                <input x-ref="search" x-model="query" @keydown.enter.prevent="searchEnter()" type="search" autocomplete="off"
                       class="input py-3 pr-24 pl-11 text-base" placeholder="Cari nama / kode produk atau scan barcode…">
                <span class="pointer-events-none absolute top-1/2 right-3 hidden -translate-y-1/2 items-center gap-1 text-xs text-slate-400 sm:flex">
                    <x-icon name="barcode" class="size-4" /> F2
                </span>
            </div>
            <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                <button type="button" @click="categoryId = null" class="btn btn-sm shrink-0 rounded-full"
                        :class="categoryId === null ? 'bg-slate-900 text-white' : 'btn-secondary'">Semua</button>
                @foreach ($categories as $category)
                    <button type="button" @click="categoryId = {{ $category->category_id }}" class="btn btn-sm shrink-0 rounded-full"
                            :class="categoryId === {{ $category->category_id }} ? 'bg-slate-900 text-white' : 'btn-secondary'">{{ $category->nama_kategori }}</button>
                @endforeach
            </div>
        </div>

        <div class="h-[50vh] overflow-y-auto p-4 md:h-auto md:flex-1">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                <template x-for="p in filteredProducts" :key="p.id">
                    <button type="button" @click="add(p); focusSearch()" :disabled="stockLeft(p) <= 0"
                            class="card group flex flex-col p-3 text-left transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-400 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                            :class="lastAddedId === p.id && 'ring-2 ring-brand-500'">
                        <span class="font-mono text-[11px] text-slate-400" x-text="p.kode"></span>
                        <span class="mt-1 line-clamp-2 min-h-10 text-sm leading-5 font-semibold text-slate-800" x-text="p.nama"></span>
                        <span class="mt-2 flex items-end justify-between gap-2">
                            <span class="font-bold text-slate-900" x-text="rupiah(p.harga)"></span>
                            <span class="badge" :class="stockLeft(p) <= 0 ? 'badge-red' : (stockLeft(p) <= 5 ? 'badge-yellow' : 'badge-gray')"
                                  x-text="stockLeft(p) <= 0 ? 'Habis' : 'Stok ' + stockLeft(p)"></span>
                        </span>
                    </button>
                </template>
            </div>
            <template x-if="!filteredProducts.length">
                <x-empty icon="search" message="Produk tidak ditemukan." />
            </template>
        </div>
    </section>

    {{-- ============ Keranjang ============ --}}
    <aside class="flex min-h-[60vh] min-w-0 flex-col border-t border-slate-200 bg-white md:min-h-0 md:border-t-0 md:border-l">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div class="flex items-center gap-2">
                <x-icon name="cart" class="size-5 text-slate-500" />
                <h2 class="font-semibold text-slate-900">Keranjang</h2>
                <span class="badge badge-blue" x-show="itemCount" x-text="itemCount + ' item'"></span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm text-red-600" x-show="cart.length" @click="clearCart()">
                <x-icon name="trash" class="size-4" />Kosongkan
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="!cart.length">
                <x-empty icon="cart" message="Keranjang kosong. Scan barcode atau pilih produk." class="h-full" />
            </template>
            <ul class="divide-y divide-slate-100">
                <template x-for="item in cart" :key="item.id">
                    <li class="px-4 py-3" :class="lastAddedId === item.id && 'bg-brand-50'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800" x-text="item.nama"></p>
                                <p class="text-xs text-slate-500" x-text="rupiah(item.harga)"></p>
                            </div>
                            <button type="button" @click="remove(item)" class="text-slate-400 hover:text-red-600" aria-label="Hapus"><x-icon name="x" class="size-4" /></button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center rounded-lg ring-1 ring-slate-300">
                                <button type="button" @click="decrement(item)" class="grid size-8 place-items-center text-slate-600 hover:bg-slate-100" aria-label="Kurangi"><x-icon name="minus" class="size-4" /></button>
                                <input type="text" inputmode="numeric" :value="item.qty" @change="setQty(item, $event.target.value); $event.target.value = item.qty"
                                       @keydown.enter.prevent="$event.target.blur()" class="h-8 w-12 border-x border-slate-300 text-center text-sm font-semibold tabular-nums outline-none" aria-label="Jumlah">
                                <button type="button" @click="increment(item)" class="grid size-8 place-items-center text-slate-600 hover:bg-slate-100" aria-label="Tambah"><x-icon name="plus" class="size-4" /></button>
                            </div>
                            <span class="text-sm font-bold text-slate-900 tabular-nums" x-text="rupiah(item.harga * item.qty)"></span>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <div class="space-y-3 border-t border-slate-200 bg-slate-50 p-4">
            <div class="flex justify-between text-sm text-slate-600">
                <span>Subtotal</span><span class="font-medium tabular-nums" x-text="rupiah(subtotal)"></span>
            </div>
            <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
                <span>Diskon</span>
                <div class="flex items-center gap-2">
                    <div class="flex rounded-lg bg-white p-0.5 ring-1 ring-slate-300">
                        <button type="button" @click="diskonMode = 'nominal'; diskonInput = ''" class="rounded-md px-2 py-1 text-xs font-semibold" :class="diskonMode === 'nominal' ? 'bg-slate-900 text-white' : 'text-slate-500'">Rp</button>
                        <button type="button" @click="diskonMode = 'persen'; diskonInput = ''" class="rounded-md px-2 py-1 text-xs font-semibold" :class="diskonMode === 'persen' ? 'bg-slate-900 text-white' : 'text-slate-500'">%</button>
                    </div>
                    <input type="text" inputmode="numeric" x-model="diskonInput" :disabled="!cart.length"
                           @input="if (diskonMode === 'nominal') diskonInput = formatNominal($event.target.value)"
                           class="input w-28 py-1.5 text-right tabular-nums" placeholder="0" aria-label="Diskon">
                </div>
            </div>
            <div class="flex justify-between text-sm text-slate-600" x-show="diskon > 0">
                <span>Potongan</span><span class="font-medium text-red-600 tabular-nums" x-text="'-' + rupiah(diskon)"></span>
            </div>
            <div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-3">
                <span class="text-sm font-semibold text-slate-700">Total</span>
                <span class="text-3xl font-bold tracking-tight text-slate-900" x-text="rupiah(total)"></span>
            </div>
            <button type="button" @click="openPayment()" :disabled="!cart.length" class="btn btn-primary btn-lg w-full py-3.5 text-lg">
                <x-icon name="banknotes" class="size-6" />Bayar <span class="rounded bg-white/20 px-1.5 py-0.5 text-xs font-medium">F9</span>
            </button>
        </div>
    </aside>

    {{-- ============ Modal pembayaran (UC-03) ============ --}}
    <div x-show="showPayment" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/60 p-0 sm:items-center sm:p-4"
         x-transition.opacity @click.self="if (!processing) closePayment()">
        <div class="max-h-[95vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-xl sm:max-w-lg sm:rounded-2xl" x-transition
             role="dialog" aria-modal="true" aria-labelledby="payment-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 id="payment-title" class="text-lg font-bold text-slate-900">Pembayaran</h2>
                <button type="button" @click="closePayment()" :disabled="processing" class="text-slate-400 hover:text-slate-600" aria-label="Tutup"><x-icon name="x" /></button>
            </div>

            <form @submit.prevent="submit()" class="space-y-5 p-6">
                <div class="rounded-xl bg-slate-900 p-5 text-center text-white">
                    <p class="text-sm text-slate-300">Total yang harus dibayar</p>
                    <p class="mt-1 text-4xl font-bold tracking-tight" x-text="rupiah(total)"></p>
                    <p class="mt-1 text-xs text-slate-400" x-text="itemCount + ' item' + (diskon > 0 ? ' · diskon ' + rupiah(diskon) : '')"></p>
                </div>

                <div>
                    <p class="label">Metode pembayaran</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['tunai' => ['Tunai', 'banknotes'], 'transfer' => ['Transfer', 'bank'], 'qris' => ['QRIS', 'qr']] as $key => [$label, $icon])
                            <button type="button" @click="selectMetode('{{ $key }}')"
                                    class="flex flex-col items-center gap-1.5 rounded-xl p-3 text-sm font-semibold ring-1 transition"
                                    :class="metode === '{{ $key }}' ? 'bg-brand-50 text-brand-700 ring-2 ring-brand-500' : 'text-slate-600 ring-slate-300 hover:bg-slate-50'">
                                <x-icon :name="$icon" class="size-6" />{{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Tunai --}}
                <div x-show="metode === 'tunai'" class="space-y-3">
                    <div>
                        <label for="uang" class="label">Uang diterima</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-lg font-semibold text-slate-400">Rp</span>
                            <input id="uang" x-ref="cash" type="text" inputmode="numeric" autocomplete="off" x-model="uangInput"
                                   @input="uangInput = formatNominal($event.target.value)"
                                   class="input py-3 pl-12 text-2xl font-bold tabular-nums" placeholder="0">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(nominal, i) in quickCash" :key="nominal">
                            <button type="button" @click="uangInput = formatNominal(nominal)" class="btn btn-secondary btn-sm"
                                    x-text="i === 0 ? 'Uang pas' : rupiah(nominal)"></button>
                        </template>
                    </div>
                    <div class="flex items-center justify-between rounded-xl p-4 ring-1"
                         :class="uangDiterima >= total ? 'bg-emerald-50 ring-emerald-200' : 'bg-slate-50 ring-slate-200'">
                        <span class="text-sm font-semibold text-slate-700" x-text="uangDiterima >= total ? 'Kembalian' : 'Kurang'"></span>
                        <span class="text-2xl font-bold tabular-nums" :class="uangDiterima >= total ? 'text-emerald-700' : 'text-slate-500'"
                              x-text="rupiah(uangDiterima >= total ? kembalian : total - uangDiterima)"></span>
                    </div>
                </div>

                {{-- Transfer --}}
                <div x-show="metode === 'transfer'" x-cloak class="space-y-3">
                    <div class="rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-slate-200">
                        <p class="text-slate-500">Transfer ke rekening toko</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ config('tokokasir.rekening') ?: 'Atur TOKO_REKENING di file .env' }}</p>
                    </div>
                    <div>
                        <label for="ref-transfer" class="label">No. referensi / nama pengirim <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="ref-transfer" x-ref="refTransfer" x-model="referensi" type="text" maxlength="100" class="input">
                    </div>
                </div>

                {{-- QRIS --}}
                <div x-show="metode === 'qris'" x-cloak class="space-y-3">
                    <div class="flex flex-col items-center rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        @if ($qrisExists)
                            <img src="{{ asset($qrisPath) }}" alt="QRIS {{ config('tokokasir.nama') }}" class="size-48 rounded-lg bg-white object-contain p-2">
                        @else
                            <div class="grid size-40 place-items-center rounded-lg border-2 border-dashed border-slate-300 bg-white text-slate-400">
                                <x-icon name="qr" class="size-16" />
                            </div>
                            <p class="mt-2 text-center text-xs text-slate-500">Letakkan gambar QRIS statis toko di <code>public/{{ $qrisPath }}</code></p>
                        @endif
                        <p class="mt-2 text-sm font-semibold text-slate-700">Minta pelanggan memindai QRIS toko</p>
                    </div>
                    <div>
                        <label for="ref-qris" class="label">No. referensi QRIS <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input x-ref="refQris" id="ref-qris" x-model="referensi" type="text" maxlength="100" class="input">
                    </div>
                </div>

                <p x-show="metode !== 'tunai'" x-cloak class="flex gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 ring-1 ring-amber-200">
                    <x-icon name="info" class="size-4 shrink-0" />Pembayaran non-tunai dicatat manual. Pastikan dana sudah diterima sebelum konfirmasi.
                </p>

                <div x-show="error" x-cloak class="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">
                    <x-icon name="x-circle" class="size-5 shrink-0" /><span x-text="error"></span>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="closePayment()" :disabled="processing" class="btn btn-secondary btn-lg flex-1">Batal</button>
                    <button type="submit" :disabled="!canSubmit" class="btn btn-success btn-lg flex-[2]">
                        <svg x-show="processing" class="size-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <x-icon name="check" class="size-5" x-show="!processing" />
                        <span x-text="processing ? 'Menyimpan…' : 'Konfirmasi Pembayaran'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Notifikasi singkat --}}
    <div x-show="toast.show" x-cloak x-transition
         class="fixed bottom-6 left-1/2 z-[60] flex -translate-x-1/2 items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
         :class="toast.type === 'error' ? 'bg-red-600' : 'bg-slate-900'" role="status">
        <x-icon name="alert" class="size-5" /><span x-text="toast.message"></span>
    </div>
</div>
@endsection
