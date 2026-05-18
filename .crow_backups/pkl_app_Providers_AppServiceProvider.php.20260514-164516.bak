<?php

namespace App\Providers;

use App\Models\AppBranding;
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
        try {
            View::share('appBranding', AppBranding::current());
        } catch (\Exception $e) {
            View::share('appBranding', null);
        }
        
        \Illuminate\Pagination\Paginator::defaultView('pagination::kips');
    }
}
