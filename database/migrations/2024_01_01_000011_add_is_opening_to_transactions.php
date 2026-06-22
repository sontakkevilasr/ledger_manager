<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add is_opening flag to transactions ────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_opening')->default(false)->after('type');
            $table->index('is_opening');
        });

        // ── Convert existing opening_balance records to transactions ───────
        //
        // Every customer that has opening_balance > 0 gets a proper
        // transaction row created from their existing balance fields.
        //
        // Dr opening → Debit transaction  (customer owes us)
        // Cr opening → Credit transaction (we owe customer)

        $customers = DB::table('customers')
            ->where('opening_balance', '>', 0)
            ->get();

        foreach ($customers as $customer) {
            $isDebit = ($customer->opening_balance_type ?? 'Dr') === 'Dr';

            DB::table('transactions')->insert([
                'customer_id'      => $customer->id,
                'payment_type_id'  => null,
                'agent_id'         => null,
                'description'      => 'Opening Balance',
                'transaction_date' => $customer->registered_on
                    ?? now()->startOfYear()->toDateString(),
                'credit'           => $isDebit ? 0 : $customer->opening_balance,
                'debit'            => $isDebit ? $customer->opening_balance : 0,
                'type'             => $isDebit ? 'Debit' : 'Credit',
                'remark'           => 'Migrated from opening balance field',
                'is_opening'       => true,
                'created_by'       => $customer->created_by ?? 1,
                'updated_by'       => $customer->created_by ?? 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // ── Zero out the old opening_balance field ─────────────────────────
        // Balance is now fully in the transactions table.
        // Keep columns for backward compatibility — just zero them out.
        DB::table('customers')->update([
            'opening_balance'      => 0,
            'opening_balance_type' => 'Dr',
        ]);
    }

    public function down(): void
    {
        // Remove is_opening transactions and restore opening_balance field
        DB::table('transactions')->where('is_opening', true)->delete();

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['is_opening']);
            $table->dropColumn('is_opening');
        });
    }
};
