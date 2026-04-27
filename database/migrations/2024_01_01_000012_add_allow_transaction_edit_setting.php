<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key'         => 'allow_transaction_edit',
            'value'       => '1',
            'label'       => 'Allow Edit & Delete Transactions',
            'type'        => 'boolean',
            'description' => 'When OFF, no one can edit or delete existing transactions. Useful for locking a closed financial period. Super Admin is also restricted.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'allow_transaction_edit')->delete();
    }
};
