<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;

/**
 * Permisos del módulo de Formularios. Roles base: Admin (todo) y Agente
 * (todo lo de Formularios). Editable desde la pantalla de Roles.
 */
class AgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agreements.view');
    }

    public function view(User $user, Agreement $agreement): bool
    {
        return $user->can('agreements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('agreements.create');
    }

    public function manage(User $user, Agreement $agreement): bool
    {
        return $user->can('agreements.manage');
    }

    public function extend(User $user, Agreement $agreement): bool
    {
        return $user->can('agreements.extend');
    }
}
