@extends('layouts.app')
@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="$user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna'" :subtitle="$user->exists ? $user->email : null">
        <a href="{{ route('admin.pengguna.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
    </x-page-header>

    <form method="POST" action="{{ $user->exists ? route('admin.pengguna.update', $user) : route('admin.pengguna.store') }}" class="card">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="nama" class="label">Nama lengkap</label>
                <input id="nama" name="nama" value="{{ old('nama', $user->nama) }}" required maxlength="100" class="input @error('nama') input-error @enderror">
                <x-field-error name="nama" />
            </div>
            <div>
                <label for="email" class="label">Email (untuk login)</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="100" autocomplete="off" class="input @error('email') input-error @enderror">
                <x-field-error name="email" />
            </div>
            <div>
                <label for="role" class="label">Peran</label>
                <select id="role" name="role" required class="input @error('role') input-error @enderror">
                    @foreach (\App\Models\User::ROLES as $key => $label)
                        <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-field-error name="role" />
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" type="password" name="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" class="input @error('password') input-error @enderror">
                <p class="hint">{{ $user->exists ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.' }}</p>
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="input">
            </div>
            <label class="flex items-start gap-3 sm:col-span-2">
                <input type="checkbox" name="status" value="1" @checked(old('status', $user->status)) class="mt-0.5 size-4 rounded border-slate-300">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Akun aktif</span>
                    <span class="block text-xs text-slate-500">Akun nonaktif tidak dapat masuk ke sistem.</span>
                </span>
            </label>
        </div>
        <div class="flex justify-end gap-3 rounded-b-xl bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.pengguna.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" />Simpan</button>
        </div>
    </form>
</div>
@endsection
