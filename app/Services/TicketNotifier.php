<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\NotificationRule;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resuelve qué usuarios deben enterarse de un evento de ticket y por qué
 * canal, combinando las NotificationRule administrables (por categoría →
 * grupo) con un aviso directo al asesor asignado y, al crear el ticket, a
 * todo el grupo "Related to" — docs/SPEC_DESARROLLO.md sección 8.1.
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

        foreach ($recipients as $userId => $entry) {
            if ($actor && $actor->id === $userId) {
                continue;
            }

            $channels = array_keys(array_filter($entry['channels']));

            if (empty($channels)) {
                continue;
            }

            $trackingToken = in_array('mail', $channels, true) ? (string) Str::uuid() : null;

            $notification = new TicketEventNotification($ticket, $event, $actor, $payload, $channels, $trackingToken);

            $emailLog = $trackingToken ? EmailLog::create([
                'tracking_token' => $trackingToken,
                'to_email' => $entry['user']->email,
                'to_name' => $entry['user']->name,
                'user_id' => $entry['user']->id,
                'ticket_id' => $ticket->id,
                'event' => $event,
                'purpose' => TicketEventNotification::purposeLabel($event),
                'subject' => $notification->subject(),
                'body_html' => (string) $notification->toMail($entry['user'])->render(),
                'status' => 'pending',
            ]) : null;

            try {
                $entry['user']->notify($notification);

                $emailLog?->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (Throwable $e) {
                $emailLog?->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

                report($e);
            }
        }
    }
}
