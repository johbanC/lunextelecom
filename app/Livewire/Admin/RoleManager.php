<?php

namespace App\Livewire\Admin;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pantalla de gestión de roles: el Admin crea roles y activa/desactiva
 * permisos por rol, agrupados por módulo. El rol "Admin" está bloqueado
 * (siempre tiene todos los permisos y no se puede borrar).
 */
class RoleManager extends Component
{
    public ?int $selectedRoleId = null;

    public string $roleName = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public bool $showForm = false;

    public bool $creatingNew = false;

    protected const LOCKED_ROLE = 'Admin';

    public function mount(): void
    {
        $this->authorize('roles.manage');
    }

    /**
     * Etiqueta legible de cada permiso (para los checkboxes).
     *
     * @return array<string, string>
     */
    public function permissionLabels(): array
    {
        return [
            'agreements.view' => __('See the forms list and detail'),
            'agreements.create' => __('Generate signing links'),
            'agreements.manage' => __('Mark signed documents as handled'),
            'agreements.extend' => __('Extend a link\'s expiry'),
            'users.manage' => __('Create users and assign roles'),
            'roles.manage' => __('Manage roles and permissions'),
            'tickets.view.own' => __('See own tickets'),
            'tickets.view.group' => __('See the group\'s tickets'),
            'tickets.view.all' => __('See every ticket'),
            'tickets.create' => __('Create tickets'),
            'tickets.comment' => __('Comment on tickets'),
            'tickets.attach' => __('Attach files to tickets'),
            'tickets.change_status' => __('Change ticket status'),
            'tickets.edit_fields' => __('Edit ticket field values'),
            'tickets.reassign' => __('Reassign tickets'),
            'catalog.manage' => __('Manage the catalog (categories, issues, fields)'),
            'notification_rules.manage' => __('Manage notification rules'),
            'reports.view.group' => __('See the group\'s reports'),
            'reports.view.all' => __('See global reports'),
            'reports.export' => __('Export reports to CSV'),
            'groups.manage' => __('Manage groups and their members'),
            'email_log.view' => __('See the sent-email audit log'),
            'help.manage' => __('Manage the help center'),
        ];
    }

    public function selectRole(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        $this->selectedRoleId = $role->id;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->creatingNew = false;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function newRole(): void
    {
        $this->selectedRoleId = null;
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->creatingNew = true;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorize('roles.manage');

        $validated = $this->validate([
            'roleName' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->ignore($this->selectedRoleId),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', Rule::in(Permission::pluck('name'))],
        ]);

        if ($this->isLocked()) {
            $this->addError('roleName', __('The Admin role cannot be edited.'));

            return;
        }

        $role = $this->selectedRoleId
            ? Role::findOrFail($this->selectedRoleId)
            : Role::create(['name' => $validated['roleName']]);

        $role->name = $validated['roleName'];
        $role->save();
        $role->syncPermissions($validated['selectedPermissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->selectedRoleId = $role->id;
        $this->creatingNew = false;
        session()->flash('status', __('Role saved.'));
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorize('roles.manage');

        $role = Role::withCount('users')->findOrFail($roleId);

        if ($role->name === self::LOCKED_ROLE) {
            $this->addError('roleName', __('The Admin role cannot be deleted.'));

            return;
        }

        if ($role->users_count > 0) {
            $this->addError('roleName', __('This role has :n user(s) assigned — reassign them before deleting it.', ['n' => $role->users_count]));

            return;
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->reset(['selectedRoleId', 'roleName', 'selectedPermissions', 'showForm', 'creatingNew']);
        session()->flash('status', __('Role deleted.'));
    }

    public function closeForm(): void
    {
        $this->reset(['selectedRoleId', 'roleName', 'selectedPermissions', 'showForm', 'creatingNew']);
        $this->resetErrorBag();
    }

    protected function isLocked(): bool
    {
        return $this->selectedRoleId
            && Role::find($this->selectedRoleId)?->name === self::LOCKED_ROLE;
    }

    public function render(): View
    {
        return view('livewire.admin.role-manager', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get(),
            'permissionGroups' => RolesAndPermissionsSeeder::GROUPS,
            'labels' => $this->permissionLabels(),
            'locked' => $this->isLocked(),
        ]);
    }
}
