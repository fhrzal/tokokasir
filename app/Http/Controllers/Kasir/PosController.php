<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * UC-02 Mengelola Transaksi Penjualan dan UC-03 Pembayaran Multi-Metode.
 */
class PosController extends Controller
{
    public function index(Request $request): View
    {
        return view('kasir.pos', [
            'shift' => $request->attributes->get('shift'),
            'products' => $this->productPayload(),
            'categories' => Category::orderBy('nama_kategori')->get(['category_id', 'nama_kategori']),
        ]);
    }

    /** Data produk terbaru (dipakai untuk menyegarkan stok setelah transaksi). */
    public function products(): JsonResponse
    {
        return response()->json($this->productPayload());
    }

    public function store(Request $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'metode_bayar' => ['required', Rule::in(array_keys(config('tokokasir.metode_bayar')))],
            'uang_diterima' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'referensi_bayar' => ['nullable', 'string', 'max:100'],
            'client_ref' => ['nullable', 'uuid'],
        ], [
            'items.required' => 'Keranjang belanja masih kosong.',
        ]);

        $transaction = $checkout->checkout($request->user(), $request->attributes->get('shift'), $data);

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'kode_transaksi' => $transaction->kode_transaksi,
            'struk_url' => route('struk.show', ['transaction' => $transaction, 'baru' => 1]),
        ], 201);
    }

    public function history(Request $request): View
    {
        $shift = $request->attributes->get('shift');

        return view('kasir.history', [
            'shift' => $shift,
            'transactions' => $shift->transactions()->withCount('items')->latest('waktu')->paginate(20),
            'summary' => $shift->summary(),
        ]);
    }

    private function productPayload()
    {
        return Product::active()
            ->orderBy('nama_produk')
            ->get(['product_id', 'category_id', 'kode_produk', 'nama_produk', 'harga_jual', 'stok'])
            ->map(fn (Product $p) => [
                'id' => $p->product_id,
                'category_id' => $p->category_id,
                'kode' => $p->kode_produk,
                'nama' => $p->nama_produk,
                'harga' => (float) $p->harga_jual,
                'stok' => $p->stok,
            ]);
    }
}
