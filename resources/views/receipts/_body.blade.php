{{-- Isi struk (dipakai halaman cetak). Lebar dirancang untuk kertas thermal 58/80 mm. --}}
<div class="text-center">
    <p class="text-base font-bold uppercase">{{ config('tokokasir.nama') }}</p>
    @if (config('tokokasir.alamat'))<p>{{ config('tokokasir.alamat') }}</p>@endif
    @if (config('tokokasir.telepon'))<p>Telp. {{ config('tokokasir.telepon') }}</p>@endif
</div>
<div class="my-2 border-t border-dashed border-slate-500"></div>
<div class="flex justify-between"><span>No</span><span>{{ $transaction->kode_transaksi }}</span></div>
<div class="flex justify-between"><span>Tanggal</span><span>{{ $transaction->waktu->format('d/m/Y H:i') }}</span></div>
<div class="flex justify-between"><span>Kasir</span><span>{{ $transaction->user->nama }}</span></div>
<div class="my-2 border-t border-dashed border-slate-500"></div>
@foreach ($transaction->items as $item)
    <div class="mb-1">
        <p>{{ $item->nama_produk }}</p>
        <div class="flex justify-between">
            <span>{{ $item->jumlah }} x {{ rupiah($item->harga, false) }}</span>
            <span>{{ rupiah($item->subtotal, false) }}</span>
        </div>
    </div>
@endforeach
<div class="my-2 border-t border-dashed border-slate-500"></div>
<div class="flex justify-between"><span>Subtotal</span><span>{{ rupiah($transaction->subtotal, false) }}</span></div>
@if ($transaction->diskon > 0)
    <div class="flex justify-between"><span>Diskon</span><span>-{{ rupiah($transaction->diskon, false) }}</span></div>
@endif
<div class="flex justify-between text-base font-bold"><span>TOTAL</span><span>{{ rupiah($transaction->total, false) }}</span></div>
<div class="flex justify-between"><span>{{ $transaction->metodeLabel() }}</span><span>{{ rupiah($transaction->uang_diterima, false) }}</span></div>
@if ($transaction->metode_bayar === 'tunai')
    <div class="flex justify-between"><span>Kembali</span><span>{{ rupiah($transaction->kembalian, false) }}</span></div>
@elseif ($transaction->referensi_bayar)
    <div class="flex justify-between"><span>Ref</span><span>{{ $transaction->referensi_bayar }}</span></div>
@endif
<div class="my-2 border-t border-dashed border-slate-500"></div>
<p class="text-center">{{ config('tokokasir.footer_struk') }}</p>
<p class="text-center text-[10px]">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
