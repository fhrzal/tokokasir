@extends('layouts.app')
@section('title', $supplier->exists ? 'Ubah Supplier' : 'Tambah Supplier')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="$supplier->exists ? 'Ubah Supplier' : 'Tambah Supplier'">
        <a href="{{ route('admin.supplier.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" />Kembali</a>
    </x-page-header>

    <form method="POST" action="{{ $supplier->exists ? route('admin.supplier.update', $supplier) : route('admin.supplier.store') }}" class="card">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif
        <div class="space-y-5 p-6">
            <div>
                <label for="nama_supplier" class="label">Nama supplier</label>
                <input id="nama_supplier" name="nama_supplier" value="{{ old('nama_supplier', $supplier->nama_supplier) }}" required maxlength="120" autofocus
                       class="input @error('nama_supplier') input-error @enderror">
                <x-field-error name="nama_supplier" />
            </div>
            <div>
                <label for="telepon" class="label">Nomor telepon</label>
                <input id="telepon" name="telepon" type="tel" value="{{ old('telepon', $supplier->telepon) }}" maxlength="20"
                       class="input @error('telepon') input-error @enderror" placeholder="08xx-xxxx-xxxx">
                <x-field-error name="telepon" />
            </div>
            <div>
                <label for="alamat" class="label">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" maxlength="1000" class="input @error('alamat') input-error @enderror">{{ old('alamat', $supplier->alamat) }}</textarea>
                <x-field-error name="alamat" />
            </div>
        </div>
        <div class="flex justify-end gap-3 rounded-b-xl bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.supplier.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" />Simpan</button>
        </div>
    </form>
</div>
@endsection
