<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Load display scale setting once per request ────────────────────
        // Cached for 60 seconds so it is not a DB query on every page load.
        // Cache is cleared automatically when the setting is saved.
        try {
            $scaleAmounts = cache()->remember('setting.scale_amounts', 60, function () {
                return DB::table('settings')
                    ->where('key', 'scale_amounts')
                    ->value('value') === '1';
            });
        } catch (\Exception) {
            // Table may not exist yet during initial migration
            $scaleAmounts = false;
        }

        config(['app.scale_amounts' => $scaleAmounts]);

        Paginator::useBootstrap();
    }
}
