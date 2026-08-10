<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OldYearBalTransactions extends Command
{
    protected $signature   = 'app:old-year-bal {action : archive or restore}
                                {--year=2025 : Year or comma-separated years to target}
                                {--force : Skip confirmation prompt}';
    protected $description = 'Archive (soft-delete) or restore transactions matching "old year bal" on Jan 1 for specified year(s).';

    public function handle(): int
    {
        $action = strtolower($this->argument('action'));
        $years = $this->parseYears();

        if (! in_array($action, ['archive', 'restore'])) {
            $this->error('  Invalid action. Use: archive  or  restore');
            return self::FAILURE;
        }

        if ($years === false) {
            return self::FAILURE;
        }

        return $action === 'archive' ? $this->archive($years) : $this->restore($years);
    }

    // ── Archive (soft-delete) ─────────────────────────────────
    private function archive(array $years): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║   Old Year Bal — Archive (Soft Delete)       ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        $rows = $this->matchingRows($years)->get();

        if ($rows->isEmpty()) {
            $this->info('  No matching transactions found. Nothing to archive.');
            return self::SUCCESS;
        }

        $this->warn("  Found {$rows->count()} transaction(s) matching:");
        $this->line('    description LIKE \'%old year bal%\'');
        $this->line('    AND transaction_date is Jan 1 (any year)');
        $this->info('');

        $this->table(
            ['ID', 'Customer ID', 'Date', 'Debit', 'Credit', 'Description'],
            $rows->map(fn($r) => [
                $r->id,
                $r->customer_id,
                $r->transaction_date,
                number_format($r->debit, 2),
                number_format($r->credit, 2),
                str()->limit($r->description, 40),
            ])->toArray()
        );

        if (! $this->option('force')) {
            $this->warn('  These records will be SOFT DELETED (reversible with: app:old-year-bal restore).');
            $this->info('');
            if (! $this->confirm('  Proceed?')) {
                $this->info('  Aborted. No changes made.');
                return self::SUCCESS;
            }
        }

        $count = $this->matchingRows($years)->update(['deleted_at' => now()]);

        $this->info('');
        $this->info("  ✓ {$count} transaction(s) archived (soft-deleted).");
        $this->info('  To undo: php artisan app:old-year-bal restore');
        $this->info('');

        return self::SUCCESS;
    }

    // ── Restore (undo soft-delete) ────────────────────────────
    private function restore(array $years): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║   Old Year Bal — Restore                     ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        $rows = $this->matchingRows($years, onlyTrashed: true)->get();

        if ($rows->isEmpty()) {
            $this->info('  No archived transactions found matching the criteria. Nothing to restore.');
            return self::SUCCESS;
        }

        $this->info("  Found {$rows->count()} archived transaction(s) to restore:");
        $this->info('');

        $this->table(
            ['ID', 'Customer ID', 'Date', 'Debit', 'Credit', 'Description', 'Deleted At'],
            $rows->map(fn($r) => [
                $r->id,
                $r->customer_id,
                $r->transaction_date,
                number_format($r->debit, 2),
                number_format($r->credit, 2),
                str()->limit($r->description, 35),
                $r->deleted_at,
            ])->toArray()
        );

        if (! $this->option('force')) {
            if (! $this->confirm('  Restore all of the above?')) {
                $this->info('  Aborted. No changes made.');
                return self::SUCCESS;
            }
        }

        $count = $this->matchingRows($years, onlyTrashed: true)->update(['deleted_at' => null]);

        $this->info('');
        $this->info("  ✓ {$count} transaction(s) restored.");
        $this->info('');

        return self::SUCCESS;
    }

    // ── Shared query builder ──────────────────────────────────
    private function matchingRows(array $years, bool $onlyTrashed = false)
    {
        $query = DB::table('transactions')
            ->where('description', 'like', '%old year bal%')
            ->whereMonth('transaction_date', 1)
            ->whereDay('transaction_date', 1)
            ->whereIn(DB::raw('YEAR(transaction_date)'), $years);

        if ($onlyTrashed) {
            $query->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    private function parseYears()
    {
        $yearOption = $this->option('year');
        $yearStrings = array_filter(array_map('trim', explode(',', $yearOption)));

        if (empty($yearStrings)) {
            $this->error('  Invalid year value. Provide a year or comma-separated years.');
            return false;
        }

        $years = array_unique(array_map(function ($year) {
            return (int) $year;
        }, $yearStrings));

        foreach ($years as $year) {
            if ($year <= 0) {
                $this->error('  Invalid year value. Provide positive numeric year(s).');
                return false;
            }
        }

        return $years;
    }
}
