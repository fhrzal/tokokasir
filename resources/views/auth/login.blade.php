<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full">
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-slate-900 p-12 text-white lg:flex">
        <div class="absolute -right-24 -top-24 size-96 rounded-full bg-brand-500/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-16 size-96 rounded-full bg-brand-400/10 blur-3xl"></div>
        <a href="{{ route('landing') }}" class="relative flex w-fit items-center gap-3">
            <span class="grid size-11 place-items-center rounded-xl bg-brand-500"><x-icon name="store" class="size-6" /></span>
            <span class="text-xl font-bold">TokoKasir</span>
        </a>
        <div class="relative max-w-md">
            <h1 class="text-4xl font-bold leading-tight">Kasir cepat, stok rapi, laporan selalu siap.</h1>
            <p class="mt-4 text-slate-300">Sistem Point of Sale berbasis web untuk mendukung penjualan dan pengelolaan stok toko retail.</p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                <li class="flex items-center gap-3"><x-icon name="barcode" class="size-5 text-brand-300" />Transaksi dengan pencarian atau barcode scanner</li>
                <li class="flex items-center gap-3"><x-icon name="banknotes" class="size-5 text-brand-300" />Pembayaran tunai, transfer, dan QRIS</li>
                <li class="flex items-center gap-3"><x-icon name="chart" class="size-5 text-brand-300" />Dashboard penjualan, laba, dan stok</li>
            </ul>
        </div>
        <p class="relative text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('tokokasir.nama') }}</p>
    </div>

    <div class="flex flex-1 items-center justify-center p-6">
        <div class="w-full max-w-sm">
            <a href="{{ route('landing') }}" class="mb-8 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                <x-icon name="arrow-left" class="size-4" />Kembali ke beranda
            </a>
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-white"><x-icon name="store" class="size-6" /></span>
                <span class="text-xl font-bold text-slate-900">TokoKasir</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan email dan password yang diberikan admin toko.</p>

            <x-flash />

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="input @error('email') input-error @enderror" placeholder="nama@tokokasir.test">
                    <x-field-error name="email" />
                </div>
                <div x-data="{ show: false }">
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" class="input pr-10">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                            <x-icon name="eye" class="size-5" />
                        </button>
                    </div>
                    <x-field-error name="password" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-brand-600"> Ingat saya
                </label>
                <button type="submit" class="btn btn-primary btn-lg w-full">Masuk</button>
            </form>

            @if (app()->environment('local'))
                <div class="mt-8 rounded-lg bg-white p-4 text-xs text-slate-600 ring-1 ring-slate-200">
                    <p class="mb-2 font-semibold text-slate-700">Akun demo (password: <code>password</code>)</p>
                    <ul class="space-y-1">
                        <li><span class="badge badge-blue mr-1">Kasir</span> kasir@tokokasir.test</li>
                        <li><span class="badge badge-violet mr-1">Admin</span> admin@tokokasir.test</li>
                        <li><span class="badge badge-green mr-1">Pemilik</span> pemilik@tokokasir.test</li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
