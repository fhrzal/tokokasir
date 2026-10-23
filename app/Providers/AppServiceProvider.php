<?php

namespace App\Providers;

use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        // Komunikasi melalui HTTPS pada server produksi.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Jumlah produk yang perlu restock untuk badge peringatan di menu.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with('restockCount', $user && $user->hasRole('admin', 'pemilik')
                ? Product::active()->needsRestock()->count()
                : 0);
        });
    }
}
