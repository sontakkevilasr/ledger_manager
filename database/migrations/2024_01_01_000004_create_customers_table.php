<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();                                           // same IDs as old tbl_Customer

            // Basic info
            $table->string('customer_name', 150);                  // was: CustomerName NVARCHAR(50)
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 150)->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 10)->nullable();

            // Financial
            $table->decimal('opening_balance', 18, 2)->default(0.00);
            $table->enum('opening_balance_type', ['Dr', 'Cr'])->default('Dr'); // Dr=customer owes us, Cr=we owe customer  // old year carry-forward
            // NOTE: EffectiveBalance REMOVED — always computed as SUM(credit) - SUM(debit)

            // Meta
            $table->date('registered_on')->nullable();             // was: Date NVARCHAR — fixed to DATE
            $table->boolean('is_active')->default(true);           // was: bit TINYINT(1) — renamed
            // NOTE: Cr_Db column REMOVED — was always NULL

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();                                  // safe delete — keeps history

            // Indexes for search performance
            $table->index('customer_name');
            $table->index('mobile');
            $table->index('city');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
