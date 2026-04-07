<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Super Admin User ──────────────────────────────────────────────────
        $userId = DB::table('users')->insertGetId([
            'name'       => 'Maryam',
            'username'   => 'maryam',
            'email'      => 'admin@amantraders.com',
            'password'   => Hash::make('Admin@123'),   // CHANGE THIS after first login
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');

        DB::table('user_roles')->insertOrIgnore([
            'user_id' => $userId,
            'role_id' => $superAdminRoleId,
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Payment Types ─────────────────────────────────────────────────────
        $paymentTypes = [
            'Cash', 'NEFT', 'RTGS', 'IMPS', 'UPI',
            'Cheque', 'Bank Transfer', 'Other',
        ];

        foreach ($paymentTypes as $type) {
            DB::table('payment_types')->insertOrIgnore([
                'payment_type' => $type,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // ── Agents (extracted from tbl_Transaction Remark column) ─────────────
        // These are the agent names found in your existing data
        $agents = [
            'HASINUDDIN',
            'ZUBAIR BHAI',
            'IQRAR ANSARI',
            'SALEEM BHAI',
            'SHADAB',
        ];

        foreach ($agents as $agent) {
            DB::table('agents')->insertOrIgnore([
                'name'       => $agent,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
