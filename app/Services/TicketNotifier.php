<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\NotificationRule;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resuelve qué usuarios deben enterarse de un evento de ticket y por qué
 * canal, combinando las NotificationRule administrables (por categoría →
 * grupo) con un aviso directo al asesor asignado y, al crear el ticket, a
 * todo el grupo "Related to" — docs/SPEC_DESARROLLO.md sección 8.1.
 *
 * Los avisos por correo de un mismo evento se mandan como UN solo email con
 * todos los destinatarios en "To:" (no uno por persona), para que sea un
 * mismo hilo — así "responder a todos" le llega a todo el grupo. Los avisos
 * en plataforma (campana) sí van uno por usuario, porque cada quien necesita
 * su propia notificación en su cuenta.
 */
class TicketNotifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function notify(
        Ticket $ticket,
        string $event,
        ?User $actor,
        array $payload = [],
        ?int $directAssigneeId = null,
        bool $notifyRelatedGroup = false,
    ): void {
        /** @var array<int, array{user: User, channels: array<string, bool>}> $recipients */
        $recipients = [];

        if ($notifyRelatedGroup && $ticket->related_to_group_id) {
            foreach ($ticket->relatedToGroup?->members ?? [] as $member) {
                $recipients[$member->id]['user'] = $member;
                $recipients[$member->id]['channels']['mail'] = true;
                $recipients[$member->id]['channels']['database'] = true;
            }
        }

        $rules = NotificationRule::query()
            ->where('event', $event)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $ticket->category_id))
            ->where(fn ($q) => $q->whereNull('related_to_group_id')->orWhere('related_to_group_id', $ticket->related_to_group_id))
            ->with('group.members')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->group) {
                continue;
            }

            $channels = match ($rule->channel) {
                'email' => ['mail'],
                'platform' => ['database'],
                default => ['mail', 'database'],
            };

            foreach ($rule->group->members as $member) {
                foreach ($channels as $channel) {
                    $recipients[$member->id]['user'] = $member;
                    $recipients[$member->id]['channels'][$channel] = true;
                }
            }
        }

        if ($directAssigneeId) {
            $assignee = $ticket->assignee_id === $directAssigneeId
                ? $ticket->assignee
                : User::find($directAssigneeId);

            if ($assignee) {
                $recipients[$assignee->id]['user'] = $assignee;
                $recipients[$assignee->id]['channels']['mail'] = true;
                $recipients[$assignee->id]['channels']['database'] = true;
            }
        }

        /** @var array<int, User> $mailRecipients */
        $mailRecipients = [];

        foreach ($recipients as $userId => $entry) {
            if ($actor && $actor->id === $userId) {
                continue;
            }

            if (! empty($entry['channels']['database'])) {
                $entry['user']->notify(new TicketEventNotification($ticket, $event, $actor, $payload, ['database']));
            }

            if (! empty($entry['channels']['mail'])) {
                $mailRecipients[$userId] = $entry['user'];
            }
        }

        if (empty($mailRecipients)) {
            return;
        }

        static::sendBatchedMail($ticket, $event, $actor, $payload, $mailRecipients);
    }

    /**
     * Manda UN correo con todos los $recipients en "To:", en vez de uno por
     * persona — así el hilo de respuestas ("responder a todos") le sigue
     * llegando a todo el grupo, no solo a quien lo contestó primero.
     *
     * @param  array<int, User>  $recipients
     */
    protected static function sendBatchedMail(Ticket $ticket, string $event, ?User $actor, array $payload, array $recipients): void
    {
        $trackingToken = (string) Str::uuid();

        $primary = reset($recipients);

        $recipientLabel = count($recipients) > 1
            ? ($ticket->relatedToGroup?->name ? "{$ticket->relatedToGroup->name} team" : 'team')
            : $primary->name;

        $notification = new TicketEventNotification($ticket, $event, $actor, $payload, ['mail'], $trackingToken, $recipientLabel);

        $emailLog = EmailLog::create([
            'tracking_token' => $trackingToken,
            'to_email' => $primary->email,
            'to_name' => $primary->name,
            'all_recipients' => collect($recipients)->map(fn (User $u) => ['name' => $u->name, 'email' => $u->email])->values()->all(),
            'user_id' => count($recipients) === 1 ? $primary->id : null,
            'ticket_id' => $ticket->id,
            'event' => $event,
            'purpose' => TicketEventNotification::purposeLabel($event),
            'subject' => $notification->subject(),
            'body_html' => (string) $notification->toMail($primary)->render(),
            'status' => 'pending',
        ]);

        $routes = collect($recipients)->mapWithKeys(fn (User $u) => [$u->email => $u->name])->all();

        try {
            Notification::route('mail', $routes)->notify($notification);

            $emailLog->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $emailLog->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            report($e);
        }
    }
}
