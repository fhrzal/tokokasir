@extends('layouts.app')
@section('title', 'Kategori Produk')

@section('content')
<x-page-header title="Kategori Produk" subtitle="Kelompokkan produk agar mudah dicari di halaman kasir." />

<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('admin.kategori.store') }}" class="card h-fit space-y-4 p-5">
        @csrf
        <h2 class="card-title">Tambah kategori</h2>
        <div>
            <label for="nama_kategori" class="label">Nama kategori</label>
            <input id="nama_kategori" name="nama_kategori" value="{{ old('nama_kategori') }}" required maxlength="100"
                   class="input @error('nama_kategori') input-error @enderror" placeholder="Mis. Minuman">
            <x-field-error name="nama_kategori" />
        </div>
        <button type="submit" class="btn btn-primary w-full"><x-icon name="plus" class="size-4" />Simpan</button>
    </form>

    <div class="card lg:col-span-2">
        @if ($categories->isEmpty())
            <x-empty icon="tag" message="Belum ada kategori." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nama kategori</th><th class="num">Jumlah produk</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($categories as $category)
                        <tr x-data="{ editing: false }">
                            <td>
                                <span x-show="!editing" class="font-medium text-slate-800">{{ $category->nama_kategori }}</span>
                                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.kategori.update', $category) }}" class="flex gap-2">
                                    @csrf @method('PUT')
                                    <input name="nama_kategori" value="{{ $category->nama_kategori }}" required maxlength="100" class="input py-1.5">
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                    <button type="button" class="btn btn-ghost btn-sm" @click="editing = false">Batal</button>
                                </form>
                            </td>
                            <td class="num">{{ $category->products_count }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" class="btn btn-ghost btn-sm" @click="editing = true" x-show="!editing"><x-icon name="pencil" class="size-4" />Ubah</button>
                                @if ($category->products_count === 0)
                                    <form method="POST" action="{{ route('admin.kategori.destroy', $category) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm text-red-600"><x-icon name="trash" class="size-4" />Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
