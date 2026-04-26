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
        // ── Load scale_amounts setting into config once per request ────────
        //
        // Cached for 60 seconds — not a DB query on every page load.
        // Cache is cleared in SettingsController::update() on every save.
        //
        // The actual fmt_amount() / scale_divisor() helpers are defined in
        // app/helpers.php and autoloaded by Composer — always available.
        try {
            $scaleAmounts = cache()->remember('setting.scale_amounts', 60, function () {
                return DB::table('settings')
                    ->where('key', 'scale_amounts')
                    ->value('value') === '1';
            });
        } catch (\Exception $e) {
            // Table may not exist yet during initial migration run
            $scaleAmounts = false;
        }

        config(['app.scale_amounts' => $scaleAmounts]);

        // ── Load allow_customer_purge setting ──────────────────────────────
        try {
            $allowPurge = cache()->remember('setting.allow_customer_purge', 60, function () {
                return DB::table('settings')
                    ->where('key', 'allow_customer_purge')
                    ->value('value') === '1';
            });
        } catch (\Exception $e) {
            $allowPurge = false;
        }

        config(['app.allow_customer_purge' => $allowPurge]);
	Paginator::useBootstrap();
    }
}
