<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'produk_aktif' => Product::active()->count(),
                'stok_menipis' => Product::active()->lowStock()->count(),
                'stok_habis' => Product::active()->outOfStock()->count(),
                'supplier' => Supplier::count(),
            ],
            'restockProducts' => Product::active()->needsRestock()->with('category')
                ->orderBy('stok')->limit(8)->get(),
            'recentPurchases' => Purchase::with('supplier')->withCount('items')
                ->latest('tanggal')->limit(5)->get(),
            'recentMovements' => StockMovement::with('product', 'user')
                ->where('jenis', '!=', 'penjualan')->latest('waktu')->limit(6)->get(),
        ]);
    }
}
