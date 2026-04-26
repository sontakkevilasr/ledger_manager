<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('label', 150);          // human-readable label for UI
            $table->string('type', 20)->default('text'); // text | boolean | number | select
            $table->text('description')->nullable(); // helper text shown in settings UI
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Seed default settings ──────────────────────────────────────────
        DB::table('settings')->insert([
            [
                'key'         => 'scale_amounts',
                'value'       => '0',
                'label'       => 'Scale Amount Display',
                'type'        => 'boolean',
                'description' => 'When ON, all amounts are divided by 100 for display. Example: ₹1,45,000 stored → ₹1,450.00 shown. The stored value in the database is never changed.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'company_name',
                'value'       => 'Aman Traders',
                'label'       => 'Company Name',
                'type'        => 'text',
                'description' => 'Shown on printed reports and PDF headers.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'financial_year_start',
                'value'       => '04-01',
                'label'       => 'Financial Year Start',
                'type'        => 'text',
                'description' => 'Format: MM-DD. Default is April 1 (04-01) for Indian financial year.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'allow_customer_purge',
                'value'       => '0',
                'label'       => 'Allow Permanent Customer Purge',
                'type'        => 'boolean',
                'description' => 'When ON, Super Admin can permanently and irreversibly delete a customer and all their transaction history. This is intended only for ended relationships where data must be completely removed.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
