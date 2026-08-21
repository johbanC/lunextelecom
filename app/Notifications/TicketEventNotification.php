<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación disparada por eventos de ticket (creación, cambio de estado,
 * reasignación) — docs/SPEC_DESARROLLO.md sección 8.1. Los canales efectivos
 * (mail/database) se resuelven por TicketNotifier según NotificationRule.
 */
class TicketEventNotification extends Notification
{
    use Queueable;

    public const EVENT_CREATED = 'created';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_REASSIGNED = 'reassigned';

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
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]))
            ->line($this->line())
            ->action(__('View ticket'), route('admin.tickets.show', $this->ticket));

        return $mail;
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
            self::EVENT_CREATED => __('New ticket :number', ['number' => $this->ticket->ticket_number]),
            self::EVENT_STATUS_CHANGED => __('Ticket :number changed status', ['number' => $this->ticket->ticket_number]),
            self::EVENT_REASSIGNED => __('Ticket :number was reassigned', ['number' => $this->ticket->ticket_number]),
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
                'from' => __(ucwords(str_replace('_', ' ', $this->payload['from'] ?? ''))),
                'to' => __(ucwords(str_replace('_', ' ', $this->payload['to'] ?? ''))),
            ]),
            self::EVENT_REASSIGNED => __(':actor assigned ticket :number to :assignee.', [
                'actor' => $actorName,
                'number' => $this->ticket->ticket_number,
                'assignee' => $this->payload['assignee_name'] ?? __('nobody'),
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
}
