<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de grupos ("Related To"). Solo se seedea el grupo
 * "Accounting" porque es el único mencionado explícitamente como default
 * en docs/SPEC_DESARROLLO.md (sección 4, "R Account inquiry"). El resto del
 * catálogo de grupos queda pendiente de confirmar con Lunex — administrable
 * después desde el panel de Admin sin tocar código.
 */
class GroupSeeder extends Seeder
{
    public function run(): void
    {
        Group::updateOrCreate(
            ['name' => 'Accounting'],
            ['applies_to' => 'both', 'is_active' => true]
        );
    }
}
