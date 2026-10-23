<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * UC-11 Melihat Laporan Penjualan.
     */
    public function sales(Request $request): View|StreamedResponse
    {
        $range = DateRange::fromRequest($request);
        $filters = [
            'user_id' => $request->query('kasir'),
            'metode_bayar' => $request->query('metode'),
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportSales($range, $filters);
        }

        return view('pemilik.reports.sales', [
            'range' => $range,
            'summary' => $this->reports->salesSummary($range->bounds(), $filters),
            'payments' => $this->reports->paymentBreakdown($range->bounds(), $filters),
            'daily' => $this->reports->daily($range->bounds(), $filters),
            'products' => $this->reports->topProducts($range->bounds(), 15, $filters),
            'transactions' => $this->reports->transactions($range->bounds(), $filters)
                ->with('user')->withSum('items', 'jumlah')
                ->latest('waktu')->paginate(15)->withQueryString(),
            'cashiers' => User::where('role', User::ROLE_KASIR)->orderBy('nama')->get(),
        ]);
    }

    /**
     * UC-12 Memantau Stok & Produk Terlaris.
     */
    public function stock(Request $request): View
    {
        $range = DateRange::fromRequest($request, '30_hari');

        $products = Product::active()->with('category')
            ->search($request->query('q'))
            ->when($request->query('kategori'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('status') === 'aman', fn ($q) => $q->whereColumn('stok', '>', 'stok_minimum')->where('stok', '>', 0))
            ->when($request->query('status') === 'menipis', fn ($q) => $q->lowStock())
            ->when($request->query('status') === 'habis', fn ($q) => $q->outOfStock())
            ->orderByRaw('CASE WHEN stok <= 0 THEN 0 WHEN stok <= stok_minimum THEN 1 ELSE 2 END')
            ->orderBy('nama_produk')
            ->paginate(20)
            ->withQueryString();

        $totals = Product::active()
            ->selectRaw('COUNT(*) as produk, COALESCE(SUM(GREATEST(stok, 0)), 0) as unit,
                COALESCE(SUM(GREATEST(stok, 0) * harga_beli), 0) as nilai_beli,
                COALESCE(SUM(GREATEST(stok, 0) * harga_jual), 0) as nilai_jual')
            ->first();

        return view('pemilik.reports.stock', [
            'range' => $range,
            'products' => $products,
            'totals' => $totals,
            'lowCount' => Product::active()->lowStock()->count(),
            'outCount' => Product::active()->outOfStock()->count(),
            'topProducts' => $this->reports->topProducts($range->bounds(), 10),
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    /**
     * UC-13 Melihat Laba & Aktivitas Kasir.
     */
    public function profit(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        $shifts = Shift::with('user')
            ->withCount('transactions')
            ->withSum('transactions', 'total')
            ->withSum(['transactions as tunai_sum' => fn ($q) => $q->where('metode_bayar', 'tunai')], 'total')
            ->whereBetween('waktu_buka', $range->bounds())
            ->when($request->query('kasir'), fn ($q, $id) => $q->where('user_id', $id))
            ->latest('waktu_buka')
            ->paginate(15)
            ->withQueryString();

        return view('pemilik.reports.profit', [
            'range' => $range,
            'profit' => $this->reports->profitSummary($range->bounds()),
            'daily' => $this->reports->daily($range->bounds()),
            'cashiers' => $this->reports->cashierActivity($range->bounds()),
            'shifts' => $shifts,
            'cashierOptions' => User::where('role', User::ROLE_KASIR)->orderBy('nama')->get(),
        ]);
    }

    private function exportSales(DateRange $range, array $filters): StreamedResponse
    {
        $filename = 'laporan-penjualan-'.$range->start->format('Ymd').'-'.$range->end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($range, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca benar di Excel
            fputcsv($out, ['Kode Transaksi', 'Waktu', 'Kasir', 'Metode Bayar', 'Subtotal', 'Diskon', 'Total', 'Uang Diterima', 'Kembalian']);

            $this->reports->transactions($range->bounds(), $filters)
                ->with('user')
                ->orderBy('waktu')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $t) {
                        fputcsv($out, [
                            $t->kode_transaksi,
                            $t->waktu->format('Y-m-d H:i:s'),
                            $t->user->nama,
                            $t->metodeLabel(),
                            $t->subtotal,
                            $t->diskon,
                            $t->total,
                            $t->uang_diterima,
                            $t->kembalian,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
