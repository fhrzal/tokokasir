<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        $today = [CarbonImmutable::today(), CarbonImmutable::today()->endOfDay()];
        $yesterday = [CarbonImmutable::yesterday(), CarbonImmutable::yesterday()->endOfDay()];
        $last14 = [CarbonImmutable::today()->subDays(13), CarbonImmutable::today()->endOfDay()];
        $last30 = [CarbonImmutable::today()->subDays(29), CarbonImmutable::today()->endOfDay()];
        $thisMonth = [CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today()->endOfDay()];

        $daily = $reports->daily($last14);

        return view('pemilik.dashboard', [
            'today' => $reports->salesSummary($today) + ['laba' => $reports->profitSummary($today)['laba']],
            'yesterday' => $reports->salesSummary($yesterday) + ['laba' => $reports->profitSummary($yesterday)['laba']],
            'month' => $reports->profitSummary($thisMonth),
            'chart' => [
                'labels' => $daily->map(fn ($d) => $d['tanggal']->translatedFormat('d M'))->all(),
                'omzet' => $daily->pluck('omzet')->all(),
                'laba' => $daily->pluck('laba')->all(),
            ],
            'topProducts' => $reports->topProducts($last30, 5),
            'payments' => $reports->paymentBreakdown($thisMonth),
            'restockProducts' => Product::active()->needsRestock()->orderBy('stok')->limit(6)->get(),
            'restockTotal' => Product::active()->needsRestock()->count(),
            'activeShifts' => Shift::with('user')->whereNull('waktu_tutup')
                ->withCount('transactions')->withSum('transactions', 'total')->get(),
            'recentTransactions' => Transaction::with('user')->latest('waktu')->limit(6)->get(),
        ]);
    }
}
