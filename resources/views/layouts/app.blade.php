@php
    $user = auth()->user();
    $isKasir = $user->hasRole('kasir');

    $menus = match ($user->role) {
        'admin' => [
            ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.produk.index', 'active' => 'admin.produk.*', 'icon' => 'cube', 'label' => 'Data Produk'],
            ['route' => 'admin.kategori.index', 'active' => 'admin.kategori.*', 'icon' => 'tag', 'label' => 'Kategori'],
            ['route' => 'admin.supplier.index', 'active' => 'admin.supplier.*', 'icon' => 'truck', 'label' => 'Data Supplier'],
            ['route' => 'admin.barang-masuk.index', 'active' => 'admin.barang-masuk.*', 'icon' => 'inbox', 'label' => 'Barang Masuk'],
            ['route' => 'admin.stok.index', 'active' => 'admin.stok.*', 'icon' => 'adjustments', 'label' => 'Stok & Penyesuaian', 'badge' => $restockCount],
            ['route' => 'admin.pengguna.index', 'active' => 'admin.pengguna.*', 'icon' => 'users', 'label' => 'Akun Pengguna'],
        ],
        'pemilik' => [
            ['route' => 'pemilik.dashboard', 'active' => 'pemilik.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard Toko'],
            ['route' => 'pemilik.laporan.penjualan', 'active' => 'pemilik.laporan.penjualan', 'icon' => 'chart', 'label' => 'Laporan Penjualan'],
            ['route' => 'pemilik.laporan.stok', 'active' => 'pemilik.laporan.stok', 'icon' => 'archive', 'label' => 'Laporan Stok', 'badge' => $restockCount],
            ['route' => 'pemilik.laporan.laba', 'active' => 'pemilik.laporan.laba', 'icon' => 'profit', 'label' => 'Laba & Aktivitas Kasir'],
        ],
        default => [
            ['route' => 'kasir.pos', 'active' => 'kasir.pos', 'icon' => 'cart', 'label' => 'Transaksi'],
            ['route' => 'kasir.history', 'active' => 'kasir.history', 'icon' => 'receipt', 'label' => 'Riwayat'],
            ['route' => 'kasir.shift.edit', 'active' => 'kasir.shift.edit', 'icon' => 'clock', 'label' => 'Tutup Shift'],
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2222%22 fill=%22%23256abf%22/><text x=%2250%22 y=%2268%22 font-size=%2252%22 text-anchor=%22middle%22 fill=%22white%22 font-family=%22Arial%22 font-weight=%22bold%22>TK</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

@if ($isKasir)
    {{-- Kasir: navigasi atas agar area transaksi lebih luas --}}
    <div class="flex min-h-full flex-col">
        <header class="no-print sticky top-0 z-30 bg-slate-900 text-white shadow" x-data="{ open: false }">
            <div class="flex h-14 items-center gap-4 px-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold">
                    <span class="grid size-8 place-items-center rounded-lg bg-brand-500"><x-icon name="store" class="size-5" /></span>
                    <span class="hidden sm:inline">TokoKasir</span>
                </a>
                <nav class="hidden items-center gap-1 md:flex">
                    @foreach ($menus as $menu)
                        <a href="{{ route($menu['route']) }}" class="nav-link {{ request()->routeIs($menu['active']) ? 'active' : '' }}">
                            <x-icon :name="$menu['icon']" />{{ $menu['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="ml-auto flex items-center gap-3">
                    @if ($user->openShift)
                        <span class="hidden items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-300 lg:inline-flex">
                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                            Shift sejak {{ $user->openShift->waktu_buka->format('H:i') }}
                        </span>
                    @endif
                    <div class="hidden text-right text-sm leading-tight sm:block">
                        <div class="font-semibold">{{ $user->nama }}</div>
                        <div class="text-xs text-slate-400">{{ $user->roleLabel() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm bg-white/10 text-white hover:bg-white/20" title="Keluar">
                            <x-icon name="logout" class="size-4" /><span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                    <button type="button" class="md:hidden" @click="open = !open" aria-label="Menu"><x-icon name="menu" class="size-6" /></button>
                </div>
            </div>
            <nav x-show="open" x-cloak x-transition class="space-y-1 border-t border-white/10 px-4 py-3 md:hidden">
                @foreach ($menus as $menu)
                    <a href="{{ route($menu['route']) }}" class="nav-link {{ request()->routeIs($menu['active']) ? 'active' : '' }}">
                        <x-icon :name="$menu['icon']" />{{ $menu['label'] }}
                    </a>
                @endforeach
            </nav>
        </header>
        <main class="print-page flex-1 @yield('main-class', 'p-4 sm:p-6')">
            <x-flash />
            @yield('content')
        </main>
    </div>
@else
    {{-- Admin & Pemilik: sidebar --}}
    <div x-data="{ sidebar: false }" class="min-h-full">
        <div x-show="sidebar" x-cloak x-transition.opacity class="no-print fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

        <aside class="no-print fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0"
               :class="sidebar && 'translate-x-0'">
            <div class="flex h-16 items-center gap-3 px-5">
                <span class="grid size-9 place-items-center rounded-lg bg-brand-500 text-white"><x-icon name="store" class="size-5" /></span>
                <div class="leading-tight">
                    <div class="font-bold text-white">TokoKasir</div>
                    <div class="text-xs text-slate-400">{{ config('tokokasir.nama') }}</div>
                </div>
                <button type="button" class="ml-auto text-slate-400 lg:hidden" @click="sidebar = false" aria-label="Tutup menu"><x-icon name="x" /></button>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach ($menus as $menu)
                    <a href="{{ route($menu['route']) }}" class="nav-link {{ request()->routeIs($menu['active']) ? 'active' : '' }}">
                        <x-icon :name="$menu['icon']" />
                        <span class="flex-1">{{ $menu['label'] }}</span>
                        @if (! empty($menu['badge']))
                            <span class="rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950" title="Produk perlu restock">{{ $menu['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-4">
                <div class="mb-3 flex items-center gap-3">
                    <span class="grid size-9 place-items-center rounded-full bg-white/10 text-sm font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->nama, 0, 1)) }}</span>
                    <div class="min-w-0 leading-tight">
                        <div class="truncate text-sm font-semibold text-white">{{ $user->nama }}</div>
                        <div class="text-xs text-slate-400">{{ $user->roleLabel() }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn w-full bg-white/10 text-white hover:bg-white/20"><x-icon name="logout" class="size-4" />Keluar</button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="no-print sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
                <button type="button" @click="sidebar = true" aria-label="Buka menu"><x-icon name="menu" class="size-6" /></button>
                <span class="font-bold text-slate-900">TokoKasir</span>
            </header>
            <main class="print-page mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                <x-flash />
                @yield('content')
            </main>
        </div>
    </div>
@endif

@stack('scripts')
</body>
</html>
