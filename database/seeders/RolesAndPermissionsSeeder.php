<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles y permisos — docs/SPEC_DESARROLLO.md sección 7.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'tickets.view.own',
            'tickets.view.group',
            'tickets.view.all',
            'tickets.create',
            'tickets.comment',
            'tickets.attach',
            'tickets.change_status',
            'tickets.edit_fields',
            'tickets.reassign',
            'reports.view.group',
            'reports.view.all',
            'reports.export',
            'catalog.manage',
            'groups.manage',
            'users.manage',
            'notification_rules.manage',
            'help.manage',
            'email_log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $advisor = Role::findOrCreate('Asesor');
        $advisor->syncPermissions([
            'tickets.view.own',
            'tickets.create',
            'tickets.comment',
            'tickets.attach',
            'tickets.change_status',
            'tickets.edit_fields',
        ]);

        $teamLead = Role::findOrCreate('Líder de equipo');
        $teamLead->syncPermissions(array_merge($advisor->permissions->pluck('name')->all(), [
            'tickets.view.group',
            'tickets.reassign',
            'reports.view.group',
        ]));

        $director = Role::findOrCreate('Director/Administración');
        $director->syncPermissions(array_merge($teamLead->permissions->pluck('name')->all(), [
            'tickets.view.all',
            'reports.view.all',
            'reports.export',
            'email_log.view',
        ]));

        $admin = Role::findOrCreate('Admin');
        $admin->syncPermissions($permissions);
    }
}
