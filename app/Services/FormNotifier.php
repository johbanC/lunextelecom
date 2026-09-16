<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Notifications\FormSubmittedNotification;

/**
 * Notifica al grupo configurado en la plantilla (form_templates.notify_group_id)
 * cuando se completa un envío. Solo canal database — nunca correo (a diferencia
 * de AgreementNotifier, que sí manda correo para COAM Equipment y no se toca).
 */
class FormNotifier
{
    public static function notifySubmitted(FormSubmission $submission): void
    {
        $group = $submission->template->notifyGroup;

        if (! $group) {
            return;
        }

        $group->loadMissing('members');

        foreach ($group->members as $member) {
            $member->notify(new FormSubmittedNotification($submission));
        }
    }
}
