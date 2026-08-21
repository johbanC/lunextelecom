<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Administración de usuarios y roles — docs/SPEC_DESARROLLO.md sección 7.
 * Requiere permiso users.manage.
 *
 * Cuentas nuevas se crean con una contraseña aleatoria inutilizable y se les
 * envía el correo de "restablecer contraseña" (el mismo flujo de Breeze que
 * ya usa "¿Olvidaste tu contraseña?" en el login) para que la persona la
 * configure ella misma. Las cuentas nunca se eliminan —pueden tener tickets,
 * comentarios, etc. asociados—, solo se activan/desactivan.
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
        $isNew = ! $userId;

        $rules = [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', 'unique:users,email,'.($userId ?: 'NULL').',id'],
            'form.role' => ['required', 'exists:roles,name'],
        ];

        if (! $isNew) {
            $rules['form.password'] = ['nullable', 'string', 'min:8'];
        }

        $data = $this->validate($rules)['form'];

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($isNew) {
            $attributes['password'] = Hash::make(Str::random(40));
        } elseif (filled($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user = User::updateOrCreate(['id' => $userId], $attributes);
        $user->syncRoles([$data['role']]);

        if ($isNew) {
            Password::sendResetLink(['email' => $user->email]);
        }

        $this->showForm = false;
    }

    public function sendSetupEmail(int $userId): void
    {
        $this->authorize('users.manage');

        $user = User::findOrFail($userId);
        Password::sendResetLink(['email' => $user->email]);

        session()->flash('status', __('Password setup email sent to :email.', ['email' => $user->email]));
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('users.manage');

        if ($userId === Auth::id()) {
            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function render(): View
    {
        return view('livewire.admin.user-manager', [
            'users' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
