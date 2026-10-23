@extends('layouts.app')
@section('title', 'Akun Pengguna')

@section('content')
<x-page-header title="Akun Pengguna" subtitle="Kelola akun kasir, admin, dan pemilik toko.">
    <a href="{{ route('admin.pengguna.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Tambah Pengguna</a>
</x-page-header>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('admin.pengguna.index') }}" class="btn btn-sm rounded-full {{ request('role') ? 'btn-secondary' : 'bg-slate-900 text-white' }}">Semua</a>
    @foreach (\App\Models\User::ROLES as $key => $label)
        <a href="{{ route('admin.pengguna.index', ['role' => $key]) }}" class="btn btn-sm rounded-full {{ request('role') === $key ? 'bg-slate-900 text-white' : 'btn-secondary' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                @php $roleBadge = ['kasir' => 'badge-blue', 'admin' => 'badge-violet', 'pemilik' => 'badge-green'][$user->role] ?? 'badge-gray'; @endphp
                <tr class="{{ $user->status ? '' : 'opacity-60' }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="grid size-8 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->nama, 0, 1)) }}</span>
                            <span class="font-medium text-slate-800">{{ $user->nama }} @if ($user->is(auth()->user()))<span class="text-xs text-slate-400">(Anda)</span>@endif</span>
                        </div>
                    </td>
                    <td class="text-slate-600">{{ $user->email }}</td>
                    <td><span class="badge {{ $roleBadge }}">{{ $user->roleLabel() }}</span></td>
                    <td>
                        @if ($user->status)
                            <span class="badge badge-green"><x-icon name="check-circle" class="size-3.5" />Aktif</span>
                        @else
                            <span class="badge badge-gray"><x-icon name="x-circle" class="size-3.5" />Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('admin.pengguna.edit', $user) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Ubah</a>
                        @unless ($user->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.pengguna.toggle', $user) }}" class="inline"
                                  onsubmit="return confirm('{{ $user->status ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->nama }}?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-ghost btn-sm {{ $user->status ? 'text-red-600' : 'text-emerald-700' }}">
                                    <x-icon name="power" class="size-4" />{{ $user->status ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
