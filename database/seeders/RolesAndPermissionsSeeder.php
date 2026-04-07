<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Permissions ───────────────────────────────────────────────────────
        $permissions = [
            // Customers
            ['module' => 'customers',     'action' => 'view',     'display_name' => 'View Customers'],
            ['module' => 'customers',     'action' => 'create',   'display_name' => 'Add Customer'],
            ['module' => 'customers',     'action' => 'edit',     'display_name' => 'Edit Customer'],
            ['module' => 'customers',     'action' => 'delete',   'display_name' => 'Delete Customer'],

            // Transactions
            ['module' => 'transactions',  'action' => 'view',     'display_name' => 'View Transactions'],
            ['module' => 'transactions',  'action' => 'create',   'display_name' => 'Add Transaction'],
            ['module' => 'transactions',  'action' => 'edit',     'display_name' => 'Edit Transaction'],
            ['module' => 'transactions',  'action' => 'delete',   'display_name' => 'Delete Transaction'],

            // Reports
            ['module' => 'reports',       'action' => 'view',     'display_name' => 'View Reports'],
            ['module' => 'reports',       'action' => 'export',   'display_name' => 'Export Reports (PDF/Excel)'],

            // Masters
            ['module' => 'masters',       'action' => 'view',     'display_name' => 'View Masters'],
            ['module' => 'masters',       'action' => 'manage',   'display_name' => 'Manage Masters'],

            // Users
            ['module' => 'users',         'action' => 'view',     'display_name' => 'View Users'],
            ['module' => 'users',         'action' => 'manage',   'display_name' => 'Manage Users & Roles'],

            // Settings
            ['module' => 'settings',      'action' => 'manage',   'display_name' => 'Manage Settings & Backup'],

            // Logs
            ['module' => 'logs',          'action' => 'view',     'display_name' => 'View Activity Logs'],
        ];

        foreach ($permissions as &$p) {
            $p['name']       = $p['module'] . '.' . $p['action'];
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        DB::table('permissions')->insertOrIgnore($permissions);

        // ── Roles ─────────────────────────────────────────────────────────────
        $roles = [
            [
                'name'         => 'super_admin',
                'display_name' => 'Super Admin',
                'description'  => 'Full access to everything. Cannot be deleted.',
                'is_system'    => true,
            ],
            [
                'name'         => 'manager',
                'display_name' => 'Manager',
                'description'  => 'Manage customers and transactions. No settings.',
                'is_system'    => true,
            ],
            [
                'name'         => 'accountant',
                'display_name' => 'Accountant',
                'description'  => 'Add transactions and view reports. Cannot edit customers.',
                'is_system'    => false,
            ],
            [
                'name'         => 'viewer',
                'display_name' => 'Viewer',
                'description'  => 'Read-only access to dashboard and reports.',
                'is_system'    => false,
            ],
        ];

        foreach ($roles as &$r) {
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }

        DB::table('roles')->insertOrIgnore($roles);

        // ── Assign Permissions to Roles ───────────────────────────────────────
        $allPerms     = DB::table('permissions')->pluck('id', 'name');
        $roleIds      = DB::table('roles')->pluck('id', 'name');

        $rolePermMap = [
            'super_admin' => $allPerms->keys()->all(),             // all permissions

            'manager' => [
                'customers.view',    'customers.create',  'customers.edit',
                'transactions.view', 'transactions.create', 'transactions.edit',
                'reports.view',      'reports.export',
                'masters.view',
            ],

            'accountant' => [
                'customers.view',
                'transactions.view', 'transactions.create', 'transactions.edit',
                'reports.view',      'reports.export',
            ],

            'viewer' => [
                'customers.view',
                'transactions.view',
                'reports.view',
            ],
        ];

        $pivotRows = [];
        foreach ($rolePermMap as $roleName => $permNames) {
            $roleId = $roleIds[$roleName];
            foreach ($permNames as $permName) {
                if (isset($allPerms[$permName])) {
                    $pivotRows[] = [
                        'role_id'       => $roleId,
                        'permission_id' => $allPerms[$permName],
                    ];
                }
            }
        }

        DB::table('role_permissions')->insertOrIgnore($pivotRows);
    }
}
