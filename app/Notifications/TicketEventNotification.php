<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación disparada por eventos de ticket (creación, cambio de estado,
 * reasignación, comentario externo, SLA) — docs/SPEC_DESARROLLO.md sección
 * 8.1. Los canales efectivos (mail/database) se resuelven por TicketNotifier
 * según NotificationRule. El correo incluye una tarjeta con los datos del
 * ticket y la firma de quien generó el evento, para que quien lo reciba
 * entienda de qué se trata sin tener que entrar a la plataforma.
 */
class TicketEventNotification extends Notification
{
    use Queueable;

    public const EVENT_CREATED = 'created';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_REASSIGNED = 'reassigned';

    public const EVENT_COMMENT_ADDED = 'comment_added';

    public const EVENT_SLA_WARNING = 'sla_warning';

    public const EVENT_SLA_BREACHED = 'sla_breached';

    /**
     * @param  array<int, string>  $channels
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?User $actor,
        public array $payload,
        public array $channels,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('emails.branded', [
                'intro' => __('Hi :name,', ['name' => $notifiable->name]).' '.$this->line(),
                'details' => $this->details(),
                'note' => $this->note(),
                'ctaLabel' => __('View ticket'),
                'ctaUrl' => route('admin.tickets.show', $this->ticket),
                'signatureName' => $this->actor?->name,
                'signatureRole' => $this->signatureRole(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'event' => $this->event,
            'subject' => $this->subject(),
            'line' => $this->line(),
            'url' => route('admin.tickets.show', $this->ticket),
        ];
    }

    protected function subject(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => __('New ticket :number (:issue)', ['number' => $this->ticket->ticket_number, 'issue' => $this->ticket->issue->name]),
            self::EVENT_STATUS_CHANGED => __('Ticket :number changed status', ['number' => $this->ticket->ticket_number]),
            self::EVENT_REASSIGNED => __('Ticket :number was reassigned', ['number' => $this->ticket->ticket_number]),
            self::EVENT_COMMENT_ADDED => __('New comment on ticket :number', ['number' => $this->ticket->ticket_number]),
            self::EVENT_SLA_WARNING => __('Ticket :number is close to its SLA', ['number' => $this->ticket->ticket_number]),
            self::EVENT_SLA_BREACHED => __('Ticket :number breached its SLA', ['number' => $this->ticket->ticket_number]),
            default => __('Ticket :number updated', ['number' => $this->ticket->ticket_number]),
        };
    }

    protected function line(): string
    {
        $actorName = $this->actor?->name ?? __('System');

        return match ($this->event) {
            self::EVENT_CREATED => __(':actor created ticket :number (:issue).', [
                'actor' => $actorName,
                'number' => $this->ticket->ticket_number,
                'issue' => $this->ticket->issue->name,
            ]),
            self::EVENT_STATUS_CHANGED => __(':actor changed ticket :number from :from to :to.', [
                'actor' => $actorName,
                'number' => $this->ticket->ticket_number,
                'from' => self::statusLabel($this->payload['from'] ?? ''),
                'to' => self::statusLabel($this->payload['to'] ?? ''),
            ]),
            self::EVENT_REASSIGNED => __(':actor assigned ticket :number to :assignee.', [
                'actor' => $actorName,
                'number' => $this->ticket->ticket_number,
                'assignee' => $this->payload['assignee_name'] ?? __('nobody'),
            ]),
            self::EVENT_COMMENT_ADDED => __(':actor added a comment on ticket :number.', [
                'actor' => $actorName,
                'number' => $this->ticket->ticket_number,
            ]),
            self::EVENT_SLA_WARNING => __('Ticket :number (:issue) has been open for :days days and is approaching its SLA limit.', [
                'number' => $this->ticket->ticket_number,
                'issue' => $this->ticket->issue->name,
                'days' => $this->payload['sla_days'] ?? '?',
            ]),
            self::EVENT_SLA_BREACHED => __('Ticket :number (:issue) has passed its :days-day SLA limit and needs attention.', [
                'number' => $this->ticket->ticket_number,
                'issue' => $this->ticket->issue->name,
                'days' => $this->payload['sla_days'] ?? '?',
            ]),
            default => __('Ticket :number was updated.', ['number' => $this->ticket->ticket_number]),
        };
    }

    /**
     * Tabla de datos del ticket que se muestra en el correo, para que quien
     * lo recibe entienda de qué se trata sin entrar a la plataforma.
     *
     * @return array<string, string>
     */
    protected function details(): array
    {
        $this->ticket->loadMissing(['category', 'issue', 'relatedToGroup', 'assignee', 'fieldValues.fieldDefinition']);

        $details = [
            __('Category') => $this->ticket->category->name,
            __('Issue') => $this->ticket->issue->name,
            __('Status') => self::statusLabel($this->ticket->status),
            __('Priority') => __(ucwords($this->ticket->priority)),
            __('Related to') => $this->ticket->relatedToGroup?->name ?? __('Unassigned group'),
            __('Assignee') => $this->ticket->assignee?->name ?? __('Unassigned'),
        ];

        foreach ($this->ticket->header as $key => $value) {
            if (filled($value)) {
                $details[ucwords(str_replace('_', ' ', $key))] = $value;
            }
        }

        foreach ($this->ticket->fieldValues as $fieldValue) {
            $decoded = $fieldValue->decodedValue();
            $details[$fieldValue->fieldDefinition->label] = is_array($decoded) ? implode(', ', $decoded) : $decoded;
        }

        return $details;
    }

    protected function note(): ?string
    {
        if ($this->event === self::EVENT_COMMENT_ADDED) {
            return $this->payload['comment'] ?? null;
        }

        return null;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            Ticket::STATUS_OPEN => __('Open'),
            Ticket::STATUS_IN_PROGRESS => __('In progress'),
            Ticket::STATUS_RESOLVED => __('Resolved'),
            Ticket::STATUS_CLOSED => __('Closed'),
            default => $status,
        };
    }

    protected function signatureRole(): ?string
    {
        if (! $this->actor) {
            return null;
        }

        $role = $this->actor->roles->first()?->name;
        $group = $this->actor->groups->first()?->name;

        return collect([$role, $group])->filter()->implode(' · ') ?: null;
    }
}
