<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Layout publik membaca kontak & kategori dari database (PBI 24).
        // Halaman yang sudah mengirim $companyProfile tidak memicu query tambahan.
        View::composer('layouts.app', function ($view) {
            if (! $view->offsetExists('companyProfile')) {
                $view->with('companyProfile', CompanyProfile::current());
            }

            $view->with('footerCategories', ProductCategory::query()
                ->whereHas('products', fn ($q) => $q->active())
                ->orderBy('name')
                ->get(['name', 'slug']));
        });
    }
}
