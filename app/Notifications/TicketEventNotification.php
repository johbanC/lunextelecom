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
 *
 * El texto del evento (subject/line, usado tanto en el correo como en la
 * notificación de campana) siempre está en inglés, sin importar el idioma
 * que tenga activo quien disparó el evento — es el idioma de trabajo
 * interno del equipo, igual que el resto de datos que persiste el sistema.
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
                'intro' => "Hi {$notifiable->name}, ".$this->line(),
                'details' => $this->details(),
                'note' => $this->note(),
                'ctaLabel' => 'View ticket',
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

    /**
     * Subject con el patrón heredado del sistema viejo (docs/
     * Lunex_Ticket_System_Discovery_MVP.docx sección 4 — "crítica de
     * preservar"): [[GRUPO]] ISSUE / CÓDIGO / TELÉFONO / #TICKET. Sirve
     * como identificador de hilo: la creación lo manda "limpio", cualquier
     * evento posterior lo reenvía con "Re:", y al pasar a Resolved
     * se antepone "done --" igual que hacía el equipo a mano.
     */
    protected function subject(): string
    {
        $base = $this->baseSubject();

        if ($this->event === self::EVENT_CREATED) {
            return $base;
        }

        if ($this->event === self::EVENT_STATUS_CHANGED
            && ($this->payload['to'] ?? null) === Ticket::STATUS_RESOLVED) {
            return "done -- Re: {$base}";
        }

        return "Re: {$base}";
    }

    protected function baseSubject(): string
    {
        $this->ticket->loadMissing(['relatedToGroup', 'issue']);

        $group = strtoupper($this->ticket->relatedToGroup?->name ?? 'Unassigned');
        $issue = strtoupper($this->ticket->issue->name);

        $segments = array_filter([
            $this->subjectCode(),
            $this->subjectPhone(),
            $this->ticket->ticket_number,
        ], fn (?string $value) => filled($value));

        return "[[{$group}]] {$issue} / ".implode(' / ', $segments);
    }

    /**
     * "CÓDIGO DEL RETAILER" del subject original — para tickets de Retailer
     * es el retailer_code; para Customer no existe ese concepto, así que se
     * usa el identificador más parecido disponible en el encabezado.
     */
    protected function subjectCode(): ?string
    {
        return $this->ticket->header['retailer_code']
            ?? $this->ticket->header['tx_id']
            ?? $this->ticket->header['full_name']
            ?? null;
    }

    /**
     * "TELÉFONO DE CONTACTO" del subject original — viene directo del
     * encabezado en tickets de Customer; en Retailer no hay un campo fijo
     * para esto, así que se busca entre los campos dinámicos del issue
     * (ej. "Caller #") uno que claramente sea un teléfono de contacto.
     */
    protected function subjectPhone(): ?string
    {
        if (filled($this->ticket->header['phone'] ?? null)) {
            return $this->ticket->header['phone'];
        }

        $this->ticket->loadMissing('fieldValues.fieldDefinition');

        foreach ($this->ticket->fieldValues as $fieldValue) {
            $label = $fieldValue->fieldDefinition->label;

            if (stripos($label, 'carrier') !== false) {
                continue;
            }

            if (stripos($label, 'caller #') !== false || stripos($label, 'phone number') !== false || stripos($label, 'contact phone') !== false) {
                $value = $fieldValue->decodedValue();

                return is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return null;
    }

    protected function line(): string
    {
        $actorName = $this->actor?->name ?? 'System';
        $number = $this->ticket->ticket_number;

        return match ($this->event) {
            self::EVENT_CREATED => "{$actorName} created ticket {$number} ({$this->ticket->issue->name}).",
            self::EVENT_STATUS_CHANGED => "{$actorName} changed ticket {$number} from ".
                self::statusLabel($this->payload['from'] ?? '').' to '.self::statusLabel($this->payload['to'] ?? '').'.',
            self::EVENT_REASSIGNED => "{$actorName} assigned ticket {$number} to ".($this->payload['assignee_name'] ?? 'nobody').'.',
            self::EVENT_COMMENT_ADDED => "{$actorName} added a comment on ticket {$number}.",
            self::EVENT_SLA_WARNING => "Ticket {$number} ({$this->ticket->issue->name}) has been open for ".
                ($this->payload['sla_days'] ?? '?').' days and is approaching its SLA limit.',
            self::EVENT_SLA_BREACHED => "Ticket {$number} ({$this->ticket->issue->name}) has passed its ".
                ($this->payload['sla_days'] ?? '?').'-day SLA limit and needs attention.',
            default => "Ticket {$number} was updated.",
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
            'Category' => $this->ticket->category->name,
            'Issue' => $this->ticket->issue->name,
            'Status' => self::statusLabel($this->ticket->status),
            'Priority' => ucwords($this->ticket->priority),
            'Related to' => $this->ticket->relatedToGroup?->name ?? 'Unassigned group',
            'Assignee' => $this->ticket->assignee?->name ?? 'Unassigned',
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
            Ticket::STATUS_NEW => 'New case',
            Ticket::STATUS_PROCESSING => 'Processing',
            Ticket::STATUS_FOLLOW_UP => 'Follow up',
            Ticket::STATUS_RESOLVED => 'Resolved',
            Ticket::STATUS_INFORMATIONAL => 'Informational',
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
