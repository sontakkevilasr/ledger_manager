<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyMigration extends Command
{
    protected $signature   = 'aman:verify-migration';
    protected $description = 'Cross-check balances between old tbl_Transaction and new transactions table';

    public function handle(): int
    {
        $this->info('');
        $this->info('Verifying balances: old system vs new system...');
        $this->info('');

        $mismatches = [];

        // Get all customers from new table
        $customers = DB::table('customers')->get();

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            // Balance in OLD table
            $oldBalance = DB::table('tbl_Transaction')
                ->where('CustomerId', (string) $customer->id)
                ->selectRaw('SUM(Credit) - SUM(Debit) as balance')
                ->value('balance') ?? 0;

            // Balance in NEW table
            $newBalance = DB::table('transactions')
                ->where('customer_id', $customer->id)
                ->selectRaw('SUM(credit) - SUM(debit) as balance')
                ->value('balance') ?? 0;

            $diff = round(abs($oldBalance - $newBalance), 2);

            if ($diff > 0.01) {    // allow 1 paisa tolerance for float rounding
                $mismatches[] = [
                    'id'          => $customer->id,
                    'name'        => $customer->customer_name,
                    'old_balance' => number_format($oldBalance, 2),
                    'new_balance' => number_format($newBalance, 2),
                    'difference'  => number_format($diff, 2),
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info('');
        $this->info('');

        if (empty($mismatches)) {
            $this->info('✓ All balances match perfectly! Safe to go live.');
        } else {
            $this->error('Mismatches found! Do NOT go live until resolved:');
            $this->table(
                ['Customer ID', 'Name', 'Old Balance', 'New Balance', 'Difference'],
                $mismatches
            );
        }

        $this->info('');
        $this->line('Total customers checked : ' . $customers->count());
        $this->line('Mismatches found        : ' . count($mismatches));

        return empty($mismatches) ? self::SUCCESS : self::FAILURE;
    }
}
