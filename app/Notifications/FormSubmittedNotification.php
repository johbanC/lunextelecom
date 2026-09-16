<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que alguien completó un formulario nuevo (form_submissions).
 * A diferencia de AgreementSignedNotification, esta SOLO usa el canal
 * database — los formularios nuevos nunca envían correo.
 */
class FormSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public FormSubmission $submission) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'form_submission_id' => $this->submission->id,
            'subject' => $this->subject(),
            'line' => $this->line(),
            'url' => route('admin.forms.show', $this->submission),
        ];
    }

    public function subject(): string
    {
        return "Form submitted: {$this->submission->template->name}";
    }

    protected function line(): string
    {
        return "A new response was received for \"{$this->submission->template->name}\".";
    }
}
