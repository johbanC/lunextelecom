<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('form_templates.manage');
    }

    public function view(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('form_templates.manage');
    }

    public function update(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }

    public function delete(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }
}
