<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * La operación actual es solo Formularios + Usuarios. Los seeders del
     * catálogo de tickets (TicketTypeSeeder, GroupSeeder, Retailer/Customer
     * CatalogSeeder) siguen disponibles y se corren a mano si se reactiva
     * el módulo de tickets:
     *   php artisan db:seed --class=TicketTypeSeeder  (etc.)
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Lunex',
            'email' => 'admin@lunextelecom.local',
            'password' => Hash::make('admin'),
        ]);
        $admin->assignRole('Admin');

        $agente = User::factory()->create([
            'name' => 'Agente Lunex',
            'email' => 'agente@lunextelecom.local',
            'password' => Hash::make('agente123'),
        ]);
        $agente->assignRole('Agente');
    }
}
