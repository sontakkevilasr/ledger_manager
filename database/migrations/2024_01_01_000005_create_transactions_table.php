<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('customer_id')                       // was: CustomerId NVARCHAR — fixed to proper FK
                  ->constrained('customers')
                  ->onDelete('restrict');                           // prevent deleting customer with transactions

            $table->foreignId('payment_type_id')                   // NEW — linked to payment_types master
                  ->nullable()
                  ->constrained('payment_types')
                  ->nullOnDelete();

            $table->foreignId('agent_id')                          // NEW — replaces free-text Remark
                  ->nullable()
                  ->constrained('agents')
                  ->nullOnDelete();

            // Entry details
            $table->string('description', 255)->nullable();        // was: NVARCHAR(50) — increased length
            $table->date('transaction_date');                       // was: Date NVARCHAR — fixed to DATE
            $table->decimal('credit', 18, 2)->default(0.00);       // was: NULL — now NOT NULL with default
            $table->decimal('debit', 18, 2)->default(0.00);        // was: NULL — now NOT NULL with default
            $table->enum('type', ['Credit', 'Debit']);             // was: NVARCHAR — now proper ENUM
            // NOTE: Balance column REMOVED — always computed as running SUM

            // Legacy remark kept as varchar for old imported data
            $table->string('remark', 150)->nullable();             // agent name free-text (old data)

            // Audit trail
            $table->foreignId('created_by')                        // NEW — which user entered this
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();                                  // safe delete — never lose financial data

            // Indexes for ledger performance
            $table->index('customer_id');
            $table->index('transaction_date');
            $table->index('type');
            $table->index(['customer_id', 'transaction_date']);     // composite — used in ledger queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
