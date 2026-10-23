<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\DateRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UC-08 Mencatat Barang Masuk.
 */
class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $range = DateRange::fromRequest($request, '30_hari');

        $query = Purchase::with('supplier', 'user')
            ->withCount('items')
            ->whereBetween('tanggal', $range->bounds())
            ->when($request->query('supplier'), fn ($q, $id) => $q->where('supplier_id', $id));

        return view('admin.purchases.index', [
            'purchases' => (clone $query)->latest('tanggal')->paginate(15)->withQueryString(),
            'totalNilai' => (clone $query)->sum('total'),
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'range' => $range,
        ]);
    }

    public function create(): View
    {
        return view('admin.purchases.create', [
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'products' => Product::active()->orderBy('nama_produk')
                ->get(['product_id', 'kode_produk', 'nama_produk', 'harga_beli', 'stok'])
                ->map(fn (Product $p) => [
                    'id' => $p->product_id,
                    'kode' => $p->kode_produk,
                    'nama' => $p->nama_produk,
                    'harga_beli' => (float) $p->harga_beli,
                    'stok' => $p->stok,
                ]),
        ]);
    }

    public function store(Request $request, PurchaseService $service): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,supplier_id'],
            'tanggal' => ['required', 'date', 'before_or_equal:now'],
            'no_faktur' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'update_harga_beli' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,product_id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ], [
            'items.required' => 'Tambahkan minimal satu produk.',
            'items.*.product_id.distinct' => 'Produk yang sama dimasukkan lebih dari sekali.',
            'tanggal.before_or_equal' => 'Tanggal barang masuk tidak boleh di masa depan.',
        ]);

        $purchase = $service->record($request->user(), $data);

        return redirect()->route('admin.barang-masuk.show', $purchase)
            ->with('success', 'Barang masuk tercatat dan stok produk telah bertambah.');
    }

    public function show(Purchase $purchase): View
    {
        return view('admin.purchases.show', [
            'purchase' => $purchase->load('supplier', 'user', 'items.product'),
        ]);
    }
}
