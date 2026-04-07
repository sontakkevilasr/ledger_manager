<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payment_types  (was tbl_PaymentTpe — fixed typo)
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('payment_type', 100)->unique();  // NEFT, Cash, Cheque, UPI ...
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // agents  (replaces free-text Remark column)
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
        Schema::dropIfExists('payment_types');
    }
};
