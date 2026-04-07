<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,   // 1. roles + permissions first
            AdminUserSeeder::class,             // 2. super admin user
            MasterDataSeeder::class,            // 3. payment types + agents
        ]);

        $this->command->info('');
        $this->command->info('✓ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. Import old MSSQL data:  php artisan aman:migrate-old-data --dry-run');
        $this->command->info('  2. Run actual migration:   php artisan aman:migrate-old-data');
        $this->command->info('  3. Verify balances:        php artisan aman:verify-migration');
        $this->command->info('');
        $this->command->warn('  Default login: admin@amantraders.com / Admin@123');
        $this->command->warn('  CHANGE the password after first login!');
    }
}
