<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * UC-06 Mengelola Produk & Kategori.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->search($request->query('q'))
            ->when($request->query('kategori'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('stok') === 'menipis', fn ($q) => $q->lowStock())
            ->when($request->query('stok') === 'habis', fn ($q) => $q->outOfStock())
            ->when($request->query('status') === 'aktif', fn ($q) => $q->where('status_aktif', true))
            ->when($request->query('status') === 'nonaktif', fn ($q) => $q->where('status_aktif', false))
            ->orderBy('nama_produk')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['status_aktif' => true, 'stok_minimum' => 5]),
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $stokAwal = (int) ($request->validate(['stok' => ['nullable', 'integer', 'min:0', 'max:1000000']])['stok'] ?? 0);
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $stokAwal, $stock, $request) {
            $product = Product::create($data + ['stok' => 0]);

            if ($stokAwal > 0) {
                $stock->move($product, $stokAwal, 'awal', $request->user(), null, 'Stok awal produk baru');
            }
        });

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /** Menonaktifkan / mengaktifkan kembali produk (produk tidak dihapus agar riwayat tetap utuh). */
    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['status_aktif' => ! $product->status_aktif]);

        return back()->with('success', $product->status_aktif
            ? "Produk {$product->nama_produk} diaktifkan."
            : "Produk {$product->nama_produk} dinonaktifkan dan tidak tampil di kasir.");
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'kode_produk' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\.]+$/',
                Rule::unique('products', 'kode_produk')->ignore($product?->product_id, 'product_id')],
            'nama_produk' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,category_id'],
            'harga_beli' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'harga_jual' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
        ], [
            'kode_produk.regex' => 'Kode produk hanya boleh berisi huruf, angka, titik, dan tanda hubung.',
        ]);

        $data['status_aktif'] = $request->boolean('status_aktif');

        return $data;
    }
}
