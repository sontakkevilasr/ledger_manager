<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MigrateOldData extends Command
{
    protected $signature   = 'aman:migrate-old-data {--dry-run : Show what would be migrated without writing}';
    protected $description = 'Migrate data from old tbl_Customer and tbl_Transaction into new clean tables';

    private int $customersMigrated    = 0;
    private int $customersSkipped     = 0;
    private int $transactionsMigrated = 0;
    private int $transactionsSkipped  = 0;
    private array $errors             = [];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('');
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║   Aman Traders — Old Data Migration       ║');
        $this->info('╚═══════════════════════════════════════════╝');

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no data will be written');
        }

        $this->info('');

        // ── Step 1: Verify old tables exist ──────────────────────────────────
        $this->info('Step 1: Checking old tables...');

        foreach (['tbl_Customer', 'tbl_Transaction'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                $this->error("Table [{$table}] not found. Import the MSSQL SQL file first.");
                return self::FAILURE;
            }
        }
        $this->line('  ✓ Old tables found');

        // ── Step 2: Migrate Customers ─────────────────────────────────────────
        $this->info('');
        $this->info('Step 2: Migrating customers...');

        $oldCustomers = DB::table('tbl_Customer')->get();
        $this->line("  Found {$oldCustomers->count()} customers in tbl_Customer");

        $bar = $this->output->createProgressBar($oldCustomers->count());
        $bar->start();

        foreach ($oldCustomers as $old) {
            try {
                // Skip if already migrated
                if (DB::table('customers')->where('id', $old->Id)->exists()) {
                    $this->customersSkipped++;
                    $bar->advance();
                    continue;
                }

                // Parse date safely
                $registeredOn = null;
                if (!empty($old->Date)) {
                    try {
                        $registeredOn = Carbon::createFromFormat('m/d/Y', trim($old->Date))->toDateString();
                    } catch (\Exception $e) {
                        $registeredOn = null; // bad date — leave null
                    }
                }

                $row = [
                    'id'              => $old->Id,
                    'customer_name'   => trim($old->CustomerName ?? ''),
                    'phone'           => trim($old->Phone ?? '') ?: null,
                    'mobile'          => trim($old->Mobile ?? '') ?: null,
                    'email'           => trim($old->Email ?? '') ?: null,
                    'address'         => trim($old->Address ?? '') ?: null,
                    'city'            => strtoupper(trim($old->City ?? '')) ?: null,
                    'state'           => ucwords(strtolower(trim($old->State ?? ''))) ?: null,
                    'zip_code'        => trim($old->ZipCode ?? '') ?: null,
                    'opening_balance' => 0.00,                    // old EffectiveBalance ignored — always 0
                    'registered_on'   => $registeredOn,
                    'is_active'       => (bool) $old->bit,
                    'created_by'      => 1,                       // assigned to super admin
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                if (! $dryRun) {
                    DB::table('customers')->insert($row);
                }

                $this->customersMigrated++;

            } catch (\Exception $e) {
                $this->errors[] = "Customer ID {$old->Id}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info('');

        // ── Step 3: Migrate Transactions ─────────────────────────────────────
        $this->info('');
        $this->info('Step 3: Migrating transactions...');

        $oldTransactions = DB::table('tbl_Transaction')->get();
        $this->line("  Found {$oldTransactions->count()} transactions in tbl_Transaction");

        // Build agent name → id map for remark matching
        $agentMap = DB::table('agents')->pluck('id', 'name')->toArray();

        $bar2 = $this->output->createProgressBar($oldTransactions->count());
        $bar2->start();

        foreach ($oldTransactions as $old) {
            try {
                // Skip if already migrated
                if (DB::table('transactions')->where('id', $old->Id)->exists()) {
                    $this->transactionsSkipped++;
                    $bar2->advance();
                    continue;
                }

                // Cast CustomerId safely (it was stored as NVARCHAR)
                $customerId = (int) trim($old->CustomerId ?? 0);

                // Verify customer exists in new table
                if (! DB::table('customers')->where('id', $customerId)->exists()) {
                    $this->errors[] = "Transaction ID {$old->Id}: Customer {$customerId} not found in new table";
                    $bar2->advance();
                    continue;
                }

                // Parse date safely
                $transactionDate = null;
                if (!empty($old->Date)) {
                    try {
                        $transactionDate = Carbon::createFromFormat('m/d/Y', trim($old->Date))->toDateString();
                    } catch (\Exception $e) {
                        $transactionDate = now()->toDateString();
                    }
                }

                // Match agent by remark name
                $agentId    = null;
                $remarkName = strtoupper(trim($old->Remark ?? ''));
                if ($remarkName && isset($agentMap[$remarkName])) {
                    $agentId = $agentMap[$remarkName];
                }

                // Normalize type
                $type = ucfirst(strtolower(trim($old->Type ?? 'credit')));
                if (! in_array($type, ['Credit', 'Debit'])) {
                    $type = 'Credit';
                }

                $row = [
                    'id'               => $old->Id,
                    'customer_id'      => $customerId,
                    'payment_type_id'  => null,                   // old data had no payment type
                    'agent_id'         => $agentId,
                    'description'      => trim($old->Description ?? '') ?: null,
                    'transaction_date' => $transactionDate,
                    'credit'           => (float) ($old->Credit ?? 0),
                    'debit'            => (float) ($old->Debit ?? 0),
                    'type'             => $type,
                    'remark'           => trim($old->Remark ?? '') ?: null,  // keep raw remark
                    'created_by'       => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                if (! $dryRun) {
                    DB::table('transactions')->insert($row);
                }

                $this->transactionsMigrated++;

            } catch (\Exception $e) {
                $this->errors[] = "Transaction ID {$old->Id}: " . $e->getMessage();
            }

            $bar2->advance();
        }

        $bar2->finish();
        $this->info('');

        // ── Step 4: Summary ───────────────────────────────────────────────────
        $this->info('');
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║   Migration Summary                       ║');
        $this->info('╚═══════════════════════════════════════════╝');

        $this->table(
            ['Item', 'Migrated', 'Skipped', 'Errors'],
            [
                ['Customers',    $this->customersMigrated,    $this->customersSkipped,    count(array_filter($this->errors, fn($e) => str_contains($e, 'Customer')))],
                ['Transactions', $this->transactionsMigrated, $this->transactionsSkipped, count(array_filter($this->errors, fn($e) => str_contains($e, 'Transaction')))],
            ]
        );

        if (!empty($this->errors)) {
            $this->warn('');
            $this->warn('Errors encountered:');
            foreach ($this->errors as $error) {
                $this->line("  • {$error}");
            }
            Log::error('OldDataMigration errors', $this->errors);
        }

        if ($dryRun) {
            $this->warn('');
            $this->warn('DRY RUN complete — no data was written. Run without --dry-run to execute.');
        } else {
            $this->info('');
            $this->info('✓ Migration complete!');
            $this->info('  Run: php artisan aman:verify-migration  to cross-check balances');
        }

        return self::SUCCESS;
    }
}
