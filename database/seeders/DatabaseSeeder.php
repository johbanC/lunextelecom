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

        $director = User::factory()->create([
            'name' => 'Director Lunex',
            'email' => 'director@lunextelecom.local',
            'password' => Hash::make('director123'),
        ]);
        $director->assignRole('Director/Administración');

        $lider = User::factory()->create([
            'name' => 'Líder de Equipo Lunex',
            'email' => 'lider@lunextelecom.local',
            'password' => Hash::make('lider123'),
        ]);
        $lider->assignRole('Líder de equipo');

        $asesor = User::factory()->create([
            'name' => 'Asesor Lunex',
            'email' => 'asesor@lunextelecom.local',
            'password' => Hash::make('asesor123'),
        ]);
        $asesor->assignRole('Asesor');
    }
}
