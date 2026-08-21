<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Reglas de acceso a tickets — docs/SPEC_DESARROLLO.md sección 7.
 * Los permisos base (tickets.view.own/group/all, etc.) se definen como
 * Gate abilities dinámicas por spatie/laravel-permission; esta policy
 * combina esos permisos con el alcance real del ticket (dueño, grupo).
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view.own')
            || $user->can('tickets.view.group')
            || $user->can('tickets.view.all');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.view.all')) {
            return true;
        }

        if ($user->can('tickets.view.group')
            && $ticket->related_to_group_id
            && $user->groups()->whereKey($ticket->related_to_group_id)->exists()) {
            return true;
        }

        if ($user->can('tickets.view.own')
            && ($ticket->created_by === $user->id || $ticket->assignee_id === $user->id)) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.comment') && $this->view($user, $ticket);
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.attach') && $this->view($user, $ticket);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.change_status') && $this->view($user, $ticket);
    }

    public function reassign(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reassign') && $this->view($user, $ticket);
    }
}
