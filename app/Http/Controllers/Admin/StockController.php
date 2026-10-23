<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\DateRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * UC-09 Melakukan Penyesuaian Stok, sekaligus riwayat pergerakan stok (kartu stok).
 */
class StockController extends Controller
{
    public const ALASAN = [
        'Stok opname',
        'Barang rusak',
        'Barang kedaluwarsa',
        'Barang hilang',
        'Koreksi pencatatan',
        'Lainnya',
    ];

    public function index(Request $request): View
    {
        $range = DateRange::fromRequest($request, '30_hari');

        $movements = StockMovement::with('product', 'user')
            ->whereBetween('waktu', $range->bounds())
            ->when($request->query('produk'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->query('jenis'), fn ($q, $jenis) => $q->where('jenis', $jenis))
            ->latest('waktu')
            ->latest('movement_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.stock.index', [
            'movements' => $movements,
            'products' => Product::orderBy('nama_produk')->get(['product_id', 'kode_produk', 'nama_produk', 'stok']),
            'range' => $range,
            'alasan' => self::ALASAN,
        ]);
    }

    public function adjust(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,product_id'],
            'mode' => ['required', Rule::in(['fisik', 'selisih'])],
            'nilai' => ['required', 'integer', 'min:-1000000', 'max:1000000'],
            'alasan' => ['required', Rule::in(self::ALASAN)],
            'keterangan' => ['nullable', 'string', 'max:100'],
        ], [
            'nilai.required' => 'Isi jumlah stok.',
        ]);

        $movement = DB::transaction(function () use ($data, $stock, $request) {
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();

            $jumlah = $data['mode'] === 'fisik'
                ? (int) $data['nilai'] - $product->stok
                : (int) $data['nilai'];

            if ($data['mode'] === 'fisik' && $data['nilai'] < 0) {
                throw ValidationException::withMessages(['nilai' => 'Stok fisik tidak boleh negatif.']);
            }

            if ($jumlah === 0) {
                throw ValidationException::withMessages(['nilai' => 'Tidak ada perubahan stok.']);
            }

            if ($product->stok + $jumlah < 0) {
                throw ValidationException::withMessages([
                    'nilai' => "Pengurangan melebihi stok saat ini ({$product->stok}).",
                ]);
            }

            $keterangan = trim($data['alasan'].(filled($data['keterangan'] ?? null) ? ': '.$data['keterangan'] : ''));

            return $stock->move($product, $jumlah, 'penyesuaian', $request->user(), null, $keterangan);
        });

        return back()->with('success', sprintf(
            'Stok %s disesuaikan (%s%d). Stok sekarang %d.',
            $movement->product->nama_produk,
            $movement->jumlah > 0 ? '+' : '',
            $movement->jumlah,
            $movement->stok_sesudah,
        ));
    }
}
