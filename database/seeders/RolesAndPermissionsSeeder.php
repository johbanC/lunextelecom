<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos — docs/SPEC_DESARROLLO.md sección 7.
 *
 * La operación actual usa solo Formularios + Usuarios, así que los roles
 * base son Admin y Agente. Los permisos de los módulos apagados (tickets,
 * reportes, grupos, correos, ayuda) se siguen registrando para que, al
 * reactivar un módulo, ya existan y se puedan asignar desde la pantalla
 * de Roles.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permisos por módulo — también los consume App\Livewire\Admin\RoleManager
     * para agrupar los checkboxes.
     *
     * @var array<string, array<int, string>>
     */
    public const GROUPS = [
        'Formularios' => [
            'agreements.view',
            'agreements.create',
            'agreements.manage',
            'agreements.extend',
            'forms.view',
            'forms.create',
            'forms.manage',
        ],
        'Plantillas de formulario' => [
            'form_templates.manage',
        ],
        'Usuarios y roles' => [
            'users.manage',
            'roles.manage',
        ],
        'Tickets' => [
            'tickets.view.own',
            'tickets.view.group',
            'tickets.view.all',
            'tickets.create',
            'tickets.comment',
            'tickets.attach',
            'tickets.change_status',
            'tickets.edit_fields',
            'tickets.reassign',
            'catalog.manage',
            'notification_rules.manage',
        ],
        'Reportes' => [
            'reports.view.group',
            'reports.view.all',
            'reports.export',
        ],
        'Grupos' => [
            'groups.manage',
        ],
        'Correos y ayuda' => [
            'email_log.view',
            'help.manage',
        ],
    ];

    public function run(): void
    {
        $all = collect(self::GROUPS)->flatten()->all();

        foreach ($all as $permission) {
            Permission::findOrCreate($permission);
        }

        $agente = Role::findOrCreate('Agente');
        $agente->syncPermissions(self::GROUPS['Formularios']);

        $admin = Role::findOrCreate('Admin');
        $admin->syncPermissions($all);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
