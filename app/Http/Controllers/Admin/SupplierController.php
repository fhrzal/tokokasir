<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UC-07 Mengelola Supplier.
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::withCount('purchases')
            ->withMax('purchases', 'tanggal')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('nama_supplier', 'like', "%{$term}%")
                ->orWhere('telepon', 'like', "%{$term}%")))
            ->orderBy('nama_supplier')
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return redirect()->route('admin.supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('admin.supplier.index')->with('success', 'Data supplier diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Supplier memiliki riwayat barang masuk sehingga tidak dapat dihapus.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_supplier' => ['required', 'string', 'max:120'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
        ]);
    }
}
