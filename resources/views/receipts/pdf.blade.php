<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $transaction->kode_transaksi }}</title>
    <style>
        @page { margin: 10pt 8pt; }
        body { font-family: 'DejaVu Sans Mono', monospace; font-size: 8pt; color: #000; margin: 0; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .big { font-size: 10pt; }
        .line { border-top: 1px dashed #000; margin: 5pt 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 0; vertical-align: top; }
        td.r { text-align: right; white-space: nowrap; }
        p { margin: 0; }
    </style>
</head>
<body>
    <div class="center">
        <p class="bold big">{{ strtoupper(config('tokokasir.nama')) }}</p>
        @if (config('tokokasir.alamat'))<p>{{ config('tokokasir.alamat') }}</p>@endif
        @if (config('tokokasir.telepon'))<p>Telp. {{ config('tokokasir.telepon') }}</p>@endif
    </div>
    <div class="line"></div>
    <table>
        <tr><td>No</td><td class="r">{{ $transaction->kode_transaksi }}</td></tr>
        <tr><td>Tanggal</td><td class="r">{{ $transaction->waktu->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="r">{{ $transaction->user->nama }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        @foreach ($transaction->items as $item)
            <tr><td colspan="2">{{ $item->nama_produk }}</td></tr>
            <tr><td>{{ $item->jumlah }} x {{ rupiah($item->harga, false) }}</td><td class="r">{{ rupiah($item->subtotal, false) }}</td></tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ rupiah($transaction->subtotal, false) }}</td></tr>
        @if ($transaction->diskon > 0)
            <tr><td>Diskon</td><td class="r">-{{ rupiah($transaction->diskon, false) }}</td></tr>
        @endif
        <tr class="bold big"><td>TOTAL</td><td class="r">{{ rupiah($transaction->total, false) }}</td></tr>
        <tr><td>{{ $transaction->metodeLabel() }}</td><td class="r">{{ rupiah($transaction->uang_diterima, false) }}</td></tr>
        @if ($transaction->metode_bayar === 'tunai')
            <tr><td>Kembali</td><td class="r">{{ rupiah($transaction->kembalian, false) }}</td></tr>
        @elseif ($transaction->referensi_bayar)
            <tr><td>Ref</td><td class="r">{{ $transaction->referensi_bayar }}</td></tr>
        @endif
    </table>
    <div class="line"></div>
    <p class="center">{{ config('tokokasir.footer_struk') }}</p>
</body>
</html>
