<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Administración de usuarios y roles — docs/SPEC_DESARROLLO.md sección 7.
 * Requiere permiso users.manage. Sin esta pantalla, asignar un rol a un
 * usuario nuevo solo era posible por tinker.
 */
class UserManager extends Component
{
    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        $this->authorize('users.manage');
    }

    public function newUser(): void
    {
        $this->authorize('users.manage');

        $this->form = ['id' => null, 'name' => '', 'email' => '', 'password' => '', 'role' => 'Asesor'];
        $this->showForm = true;
    }

    public function editUser(int $userId): void
    {
        $this->authorize('users.manage');

        $user = User::findOrFail($userId);
        $this->form = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->roles->first()?->name,
        ];
        $this->showForm = true;
    }

    public function saveUser(): void
    {
        $this->authorize('users.manage');

        $userId = $this->form['id'] ?? null;

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', 'unique:users,email,'.($userId ?: 'NULL').',id'],
            'form.password' => [$userId ? 'nullable' : 'required', 'string', 'min:8'],
            'form.role' => ['required', 'exists:roles,name'],
        ])['form'];

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (filled($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user = User::updateOrCreate(['id' => $userId], $attributes);
        $user->syncRoles([$data['role']]);

        $this->showForm = false;
    }

    public function render(): View
    {
        return view('livewire.admin.user-manager', [
            'users' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
