<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            DB::statement("SET SESSION sql_require_primary_key = 0");
        } catch (\Throwable $e) {
            // Silently ignore if the variable is not supported (e.g. local MySQL)
        }
    }
}
