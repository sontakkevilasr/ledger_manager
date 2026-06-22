<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearData extends Command
{
    protected $signature   = 'app:clear-data
                                {--force : Skip confirmation prompt (use in scripts)}';
    protected $description = 'Clear all transactional data (customers, transactions, logs). Keeps master data, roles, users, and settings.';

    // Tables to wipe — ORDER matters (child before parent for FK constraints)
    private array $clearTables = [
        'transactions',
        'customers',
        'activity_logs',
        'login_logs',
    ];

    // Tables that will NOT be touched — shown in the dry-run summary
    private array $preservedTables = [
        'users', 'user_roles',
        'roles', 'permissions', 'role_permissions',
        'payment_types', 'agents',
        'settings',
        'migrations', 'sessions', 'cache', 'jobs', 'failed_jobs',
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║       Ledger Manager — Clear Data            ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        // Show what will be cleared
        $this->warn('  Tables that will be CLEARED:');
        foreach ($this->clearTables as $table) {
            $count = DB::table($table)->count();
            $this->line(sprintf('    %-20s %s rows', $table, number_format($count)));
        }

        $this->info('');
        $this->info('  Tables that will be PRESERVED:');
        $this->line('    ' . implode(', ', $this->preservedTables));
        $this->info('');

        // Confirm
        if (! $this->option('force')) {
            $this->warn('  WARNING: This action is irreversible. All customer and transaction data will be permanently deleted.');
            $this->info('');

            if (! $this->confirm('  Are you sure you want to clear all transactional data?')) {
                $this->info('  Aborted. No changes made.');
                return self::SUCCESS;
            }

            // Second confirmation
            $typed = $this->ask('  Type DELETE to confirm');
            if ($typed !== 'DELETE') {
                $this->info('  Confirmation failed. No changes made.');
                return self::SUCCESS;
            }
        }

        $this->info('');
        $this->info('  Clearing data...');
        $this->info('');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $results = [];
            foreach ($this->clearTables as $table) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $results[$table] = $count;
                $this->line(sprintf('    ✓ %-20s cleared (%s rows)', $table, number_format($count)));
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('');
            $this->info('  ✓ Done. Database cleared successfully.');
            $this->info('');

            $total = array_sum($results);
            $this->info("  Total rows removed: {$total}");
            $this->info('');

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('  Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
