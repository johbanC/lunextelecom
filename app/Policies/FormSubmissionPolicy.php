<?php

namespace App\Policies;

use App\Models\FormSubmission;
use App\Models\User;

class FormSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('forms.view');
    }

    public function view(User $user, FormSubmission $formSubmission): bool
    {
        return $user->can('forms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('forms.create');
    }

    public function manage(User $user, ?FormSubmission $formSubmission = null): bool
    {
        return $user->can('forms.manage');
    }
}
