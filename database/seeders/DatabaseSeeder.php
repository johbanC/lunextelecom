<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TicketTypeSeeder::class,
            GroupSeeder::class,
            RetailerCatalogSeeder::class,
            CustomerCatalogSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Lunex',
            'email' => 'admin@lunextelecom.local',
            'password' => Hash::make('admin'),
        ]);
        $admin->assignRole('Admin');
    }
}
