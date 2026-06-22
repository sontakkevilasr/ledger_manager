<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            // Add Dr/Cr type for opening balance
            // Dr = customer owes Aman Traders (debit balance — we should collect)
            // Cr = Aman Traders owes customer (credit balance — we should pay)
            $table->enum('opening_balance_type', ['Dr', 'Cr'])
                  ->default('Dr')
                  ->after('opening_balance');
        });

        // ── Fix existing imported data ─────────────────────────────────────
        // Old tbl_Customer had a Cr_Db column (always NULL in your data).
        // All imported customers had opening_balance = 0, so type doesn't matter.
        // But if you have any non-zero opening balances from old system,
        // review them manually and update the type accordingly.
        DB::statement("
            UPDATE customers
            SET opening_balance_type = 'Dr'
            WHERE opening_balance_type IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('opening_balance_type');
        });
    }
};
