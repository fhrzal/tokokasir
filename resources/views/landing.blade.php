@php
    $ctaUrl = auth()->check() ? route('home') : route('login');
    $ctaLabel = auth()->check() ? 'Buka Dashboard' : 'Masuk ke Aplikasi';

    $nav = [
        '#fitur' => 'Fitur',
        '#peran' => 'Peran',
        '#alur' => 'Alur Kerja',
        '#teknologi' => 'Teknologi',
    ];

    // Permasalahan pada proposal (3.1) dan cara TokoKasir menyelesaikannya.
    $problems = [
        ['icon' => 'receipt', 'title' => 'Pencatatan transaksi manual', 'solution' => 'Setiap penjualan tercatat otomatis dan total dihitung sistem, tanpa salah hitung.'],
        ['icon' => 'banknotes', 'title' => 'Selisih uang kas', 'solution' => 'Kas awal dan kas akhir dicatat per shift, selisihnya dihitung otomatis.'],
        ['icon' => 'cube', 'title' => 'Stok tidak akurat', 'solution' => 'Stok berkurang setiap penjualan dan setiap perubahan tercatat di kartu stok.'],
        ['icon' => 'inbox', 'title' => 'Barang masuk tidak terstruktur', 'solution' => 'Riwayat pembelian per supplier tersimpan lengkap dengan harga beli dan jumlah.'],
        ['icon' => 'chart', 'title' => 'Laporan terlambat', 'solution' => 'Omzet, laba kotor, dan produk terlaris tersedia kapan saja tanpa rekap manual.'],
        ['icon' => 'users', 'title' => 'Pemantauan kasir terbatas', 'solution' => 'Jumlah transaksi, jam shift, dan selisih kas setiap kasir terlihat di satu laporan.'],
    ];

    // Fitur unggulan (proposal 2.3).
    $features = [
        ['icon' => 'barcode', 'title' => 'Scan barcode & pencarian cepat', 'text' => 'Kode dipindai, produk langsung masuk keranjang. Total, diskon, dan kembalian dihitung otomatis.'],
        ['icon' => 'qr', 'title' => 'Pembayaran multi-metode', 'text' => 'Terima pembayaran tunai, transfer, dan QRIS dalam satu layar pembayaran.'],
        ['icon' => 'printer', 'title' => 'Struk thermal & digital', 'text' => 'Cetak ke printer thermal 58/80 mm atau simpan struk sebagai PDF.'],
        ['icon' => 'clock', 'title' => 'Kas awal & kas akhir per shift', 'text' => 'Uang di laci dicocokkan dengan penjualan tunai setiap kali shift ditutup.'],
        ['icon' => 'truck', 'title' => 'Produk, supplier & barang masuk', 'text' => 'Kelola katalog, harga beli dan jual, serta catat penerimaan barang dari supplier.'],
        ['icon' => 'alert', 'title' => 'Peringatan stok minimum', 'text' => 'Produk yang menipis atau habis ditandai agar bisa segera dipesan ulang.'],
        ['icon' => 'profit', 'title' => 'Dashboard & laporan', 'text' => 'Penjualan, laba kotor, stok, produk terlaris, dan aktivitas kasir dalam grafik dan tabel.'],
        ['icon' => 'shield', 'title' => 'Hak akses per peran', 'text' => 'Kasir, admin, dan pemilik hanya melihat menu yang menjadi kewenangannya.'],
    ];

    // Aktor dan peran (proposal 2.2).
    $roles = [
        ['name' => 'Kasir', 'icon' => 'cart', 'tone' => 'bg-brand-50 text-brand-600 ring-brand-100', 'items' => [
            'Membuka dan menutup shift',
            'Mencari atau memindai produk',
            'Memproses transaksi dan diskon',
            'Menerima pembayaran tunai, transfer, QRIS',
            'Mencetak atau menyimpan struk',
        ]],
        ['name' => 'Admin', 'icon' => 'adjustments', 'tone' => 'bg-violet-50 text-violet-600 ring-violet-100', 'items' => [
            'Mengelola produk dan kategori',
            'Mengelola data supplier',
            'Mencatat barang masuk',
            'Melakukan penyesuaian stok',
            'Mengelola akun pengguna',
        ]],
        ['name' => 'Pemilik Toko', 'icon' => 'chart', 'tone' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'items' => [
            'Memantau dashboard usaha',
            'Melihat laporan penjualan dan laba',
            'Memantau stok dan produk terlaris',
            'Melihat aktivitas setiap kasir',
            'Memeriksa selisih kas per shift',
        ]],
    ];

    $steps = [
        ['title' => 'Buka shift', 'text' => 'Kasir mencatat kas awal di laci.'],
        ['title' => 'Scan produk', 'text' => 'Barcode dipindai, keranjang terisi otomatis.'],
        ['title' => 'Terima pembayaran', 'text' => 'Tunai, transfer, atau QRIS beserta kembalian.'],
        ['title' => 'Cetak struk', 'text' => 'Struk thermal atau PDF; stok langsung berkurang.'],
        ['title' => 'Tutup shift', 'text' => 'Kas akhir dihitung, selisih tercatat untuk pemilik.'],
    ];

    $team = [
        ['Muhammad Bagus Afandi', '2313020129'],
        ['Fahrizal Adi Kuswara', '2313020131'],
        ['Moh. Reno Fathkurohman', '2313020130'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TokoKasir — Sistem POS Berbasis Web untuk Toko Retail</title>
    <meta name="description" content="TokoKasir adalah aplikasi Point of Sale berbasis web untuk mencatat penjualan, mengelola stok, dan memantau laporan toko retail melalui browser.">
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">

{{-- ============ Navigasi ============ --}}
<header x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 16"
        class="fixed inset-x-0 top-0 z-50 transition"
        :class="scrolled || open ? 'bg-slate-900/95 shadow-lg backdrop-blur' : 'bg-transparent'">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-8 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5 font-bold text-white">
            <span class="grid size-9 place-items-center rounded-lg bg-brand-500"><x-icon name="store" class="size-5" /></span>
            <span class="text-lg">TokoKasir</span>
        </a>
        <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
            @foreach ($nav as $href => $label)
                <a href="{{ $href }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-3">
            <a href="{{ $ctaUrl }}" class="btn btn-primary hidden sm:inline-flex">{{ $ctaLabel }}<x-icon name="arrow-right" class="size-4" /></a>
            <button type="button" class="text-white md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                <x-icon name="menu" class="size-6" x-show="!open" />
                <x-icon name="x" class="size-6" x-show="open" x-cloak />
            </button>
        </div>
    </div>
    <nav x-show="open" x-cloak x-transition @click="open = false" class="space-y-1 border-t border-white/10 px-4 pt-2 pb-4 md:hidden">
        @foreach ($nav as $href => $label)
            <a href="{{ $href }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-200 hover:bg-white/10">{{ $label }}</a>
        @endforeach
        <a href="{{ $ctaUrl }}" class="btn btn-primary mt-2 w-full">{{ $ctaLabel }}</a>
    </nav>
</header>

<main>
    {{-- ============ Hero ============ --}}
    <section class="relative overflow-hidden bg-slate-900 pt-32 text-white sm:pt-40">
        <div class="pointer-events-none absolute -top-40 left-1/2 size-[44rem] -translate-x-1/2 rounded-full bg-brand-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] bg-[size:48px_48px] [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)]"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-200 ring-1 ring-white/15">
                    <x-icon name="cloud" class="size-4" />Cloud POS · Cukup lewat browser
                </span>
                <h1 class="mt-6 text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                    Kasir cepat, stok rapi, <span class="text-brand-300">laporan selalu siap.</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-pretty text-slate-300">
                    TokoKasir membantu toko kelontong, minimarket, dan toko kebutuhan harian mencatat penjualan,
                    mengelola persediaan, dan memantau laba dari komputer, laptop, maupun tablet.
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $ctaUrl }}" class="btn btn-primary btn-lg w-full sm:w-auto">{{ $ctaLabel }}<x-icon name="arrow-right" class="size-5" /></a>
                    <a href="#fitur" class="btn btn-lg w-full bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/15 sm:w-auto">Lihat fitur</a>
                </div>
                <ul class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-slate-400">
                    @foreach (['Tanpa instalasi aplikasi', 'Barcode scanner & printer thermal', 'Tunai, transfer, QRIS'] as $point)
                        <li class="flex items-center gap-1.5"><x-icon name="check-circle" class="size-4 text-emerald-400" />{{ $point }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Tangkapan layar dashboard pemilik + struk --}}
            <div class="relative mx-auto mt-16 max-w-6xl sm:mt-20">
                <div class="rounded-t-2xl bg-white/5 p-2 ring-1 ring-white/10 sm:p-3">
                    <div class="overflow-hidden rounded-t-xl bg-slate-800 shadow-2xl ring-1 ring-black/20">
                        <div class="flex items-center gap-2 border-b border-white/5 bg-slate-800 px-4 py-2.5">
                            <span class="size-2.5 rounded-full bg-red-400/80"></span>
                            <span class="size-2.5 rounded-full bg-amber-400/80"></span>
                            <span class="size-2.5 rounded-full bg-emerald-400/80"></span>
                            <span class="mx-auto hidden w-72 truncate rounded-md bg-slate-900/60 px-3 py-1 text-center text-xs text-slate-400 sm:block">tokokasir/pemilik/dashboard</span>
                        </div>
                        <img src="{{ asset('images/landing/dashboard.webp') }}" width="2160" height="1350"
                             alt="Dashboard pemilik toko: omzet, laba kotor, grafik 14 hari, metode pembayaran, produk terlaris, dan peringatan stok"
                             class="block w-full" fetchpriority="high">
                    </div>
                </div>
                <img src="{{ asset('images/landing/struk.webp') }}" width="604" height="1064" loading="lazy"
                     alt="Contoh struk transaksi TokoKasir"
                     class="absolute -right-6 bottom-6 hidden w-40 rotate-3 rounded-lg shadow-2xl ring-1 ring-black/10 lg:block xl:-right-20 xl:w-48">
            </div>
        </div>
    </section>

    {{-- ============ Masalah → solusi ============ --}}
    <section class="bg-slate-50 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Masalah yang diselesaikan</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-balance text-slate-900 sm:text-4xl">Dari catatan manual ke satu sistem terpadu</h2>
                <p class="mt-4 text-lg text-pretty text-slate-600">Transaksi, persediaan, dan laporan yang dulu tersebar di buku catatan dan kalkulator kini tercatat di satu tempat.</p>
            </div>
            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($problems as $item)
                    <div class="card p-6">
                        <span class="grid size-10 place-items-center rounded-lg bg-slate-100 text-slate-600"><x-icon :name="$item['icon']" class="size-5" /></span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 flex gap-2 text-sm leading-6 text-slate-600">
                            <x-icon name="arrow-right" class="mt-1 size-4 shrink-0 text-brand-500" />{{ $item['solution'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Fitur ============ --}}
    <section id="fitur" class="scroll-mt-16 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-5 lg:gap-16">
                <div class="lg:col-span-2">
                    <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Halaman kasir</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-balance text-slate-900 sm:text-4xl">Dirancang untuk kasir yang bekerja cepat saat toko ramai</h2>
                    <p class="mt-4 text-lg text-pretty text-slate-600">Kolom pencarian selalu siap menerima hasil pindaian barcode. Tombol besar dan pintasan keyboard memperpendek setiap transaksi.</p>
                    <dl class="mt-8 space-y-5">
                        @foreach ([
                            ['bolt', 'Pintasan keyboard', 'F2 untuk mencari produk, F9 untuk membayar, Enter untuk transaksi berikutnya.'],
                            ['lock', 'Tidak ada transaksi ganda', 'Tombol bayar yang tertekan dua kali atau koneksi yang terputus tidak mencatat penjualan dua kali.'],
                            ['desktop', 'Komputer, laptop, atau tablet', 'Tampilan menyesuaikan layar; cukup buka browser yang terhubung ke server toko.'],
                        ] as [$icon, $title, $text])
                            <div class="flex gap-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon :name="$icon" class="size-5" /></span>
                                <div>
                                    <dt class="font-semibold text-slate-900">{{ $title }}</dt>
                                    <dd class="mt-1 text-sm leading-6 text-slate-600">{{ $text }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="lg:col-span-3">
                    <div class="rounded-2xl bg-slate-100 p-2 ring-1 ring-slate-200 sm:p-3">
                        <img src="{{ asset('images/landing/pos.webp') }}" width="1920" height="1200" loading="lazy"
                             alt="Halaman transaksi kasir: daftar produk, keranjang belanja, dan total pembayaran"
                             class="block w-full rounded-xl shadow-xl ring-1 ring-slate-900/10">
                    </div>
                </div>
            </div>

            <div class="mt-24 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $feature)
                    <div>
                        <span class="grid size-11 place-items-center rounded-xl bg-slate-900 text-white"><x-icon :name="$feature['icon']" class="size-5" /></span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Peran ============ --}}
    <section id="peran" class="scroll-mt-16 bg-slate-50 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Tiga peran pengguna</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-balance text-slate-900 sm:text-4xl">Setiap orang mendapat halaman sesuai tugasnya</h2>
                <p class="mt-4 text-lg text-pretty text-slate-600">Setelah masuk, pengguna diarahkan ke halaman awal sesuai perannya. Menu di luar kewenangan tidak dapat diakses.</p>
            </div>
            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                @foreach ($roles as $role)
                    <div class="card flex flex-col p-7">
                        <div class="flex items-center gap-3">
                            <span class="grid size-12 place-items-center rounded-xl ring-1 {{ $role['tone'] }}"><x-icon :name="$role['icon']" class="size-6" /></span>
                            <h3 class="text-xl font-bold text-slate-900">{{ $role['name'] }}</h3>
                        </div>
                        <ul class="mt-6 space-y-3 text-sm text-slate-600">
                            @foreach ($role['items'] as $item)
                                <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-600" />{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Alur kerja ============ --}}
    <section id="alur" class="scroll-mt-16 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Alur kerja</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-balance text-slate-900 sm:text-4xl">Satu shift kasir dalam lima langkah</h2>
            </div>
            <div class="relative mt-14">
            <div class="absolute top-6 right-[10%] left-[10%] hidden h-px bg-slate-200 md:block" aria-hidden="true"></div>
            <ol class="relative grid gap-8 md:grid-cols-5 md:gap-4">
                @foreach ($steps as $i => $step)
                    <li class="relative flex gap-4 md:flex-col md:items-center md:text-center">
                        <span class="relative grid size-12 shrink-0 place-items-center rounded-full bg-brand-600 text-lg font-bold text-white ring-8 ring-white">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="font-semibold text-slate-900 md:mt-4">{{ $step['title'] }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            </div>
        </div>
    </section>

    {{-- ============ Teknologi & keandalan ============ --}}
    <section id="teknologi" class="scroll-mt-16 bg-slate-900 py-20 text-white sm:py-28">
        <div class="mx-auto grid max-w-7xl gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <p class="text-sm font-semibold tracking-wide text-brand-300 uppercase">Teknologi</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Cloud POS: data tersimpan terpusat, diakses lewat browser</h2>
                <p class="mt-4 text-lg text-pretty text-slate-300">Arsitektur client-server berbasis web. Browser kasir, admin, dan pemilik terhubung ke aplikasi Laravel yang menyimpan seluruh data di satu database MySQL.</p>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['Laravel 12' => 'Back-end MVC', 'MySQL' => 'Database relasional', 'Tailwind CSS' => 'Antarmuka responsif', 'Alpine.js' => 'Interaksi kasir'] as $name => $role)
                        <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                            <p class="font-semibold">{{ $name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $role }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="space-y-4">
                @foreach ([
                    ['shield', 'Keamanan', 'Password terenkripsi, hak akses berdasarkan peran, validasi input di server, perlindungan CSRF, dan HTTPS di server produksi.'],
                    ['lock', 'Keandalan data', 'Transaksi, barang masuk, dan penyesuaian stok disimpan secara atomik: stok tidak minus dan penjualan tidak tercatat ganda.'],
                    ['archive', 'Jejak persediaan', 'Setiap perubahan stok, baik penjualan, barang masuk, maupun penyesuaian, tercatat beserta pengguna dan waktunya.'],
                    ['profit', 'Laba yang akurat', 'Harga beli disimpan pada setiap transaksi, sehingga laba historis tidak berubah saat harga beli diperbarui.'],
                ] as [$icon, $title, $text])
                    <div class="flex gap-4 rounded-xl bg-white/5 p-5 ring-1 ring-white/10">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-500/20 text-brand-300"><x-icon :name="$icon" class="size-5" /></span>
                        <div>
                            <h3 class="font-semibold">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-300">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Ajakan ============ --}}
    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-balance text-slate-900 sm:text-4xl">Mulai bertransaksi dengan TokoKasir</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-pretty text-slate-600">Masuk menggunakan akun yang diberikan admin toko. Anda akan langsung diarahkan ke halaman sesuai peran.</p>
            <a href="{{ $ctaUrl }}" class="btn btn-primary btn-lg mt-8">{{ $ctaLabel }}<x-icon name="arrow-right" class="size-5" /></a>
        </div>
    </section>
</main>

{{-- ============ Footer ============ --}}
<footer class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
        <div>
            <div class="flex items-center gap-2.5 font-bold text-slate-900">
                <span class="grid size-9 place-items-center rounded-lg bg-brand-600 text-white"><x-icon name="store" class="size-5" /></span>
                TokoKasir
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-600">Sistem Point of Sale berbasis web untuk mendukung penjualan dan pengelolaan stok toko retail.</p>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Tim pengembang</h3>
            <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                @foreach ($team as [$name, $nim])
                    <li>{{ $name }} <span class="text-slate-400">· {{ $nim }}</span></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="academic" class="size-5 text-slate-500" />Software Capstone Project</h3>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Program Studi Teknik Informatika<br>
                Fakultas Teknik dan Ilmu Komputer<br>
                Universitas Nusantara PGRI Kediri · 2026
            </p>
        </div>
    </div>
    <div class="border-t border-slate-200 py-5 text-center text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('tokokasir.nama') }}</div>
</footer>

</body>
</html>
