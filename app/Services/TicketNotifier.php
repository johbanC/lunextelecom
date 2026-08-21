<?php

namespace App\Services;

use App\Models\NotificationRule;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;

/**
 * Resuelve qué usuarios deben enterarse de un evento de ticket y por qué
 * canal, combinando las NotificationRule administrables (por categoría →
 * grupo) con un aviso directo al asesor asignado — docs/SPEC_DESARROLLO.md
 * sección 8.1.
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
    ): void {
        /** @var array<int, array{user: User, channels: array<string, bool>}> $recipients */
        $recipients = [];

        $rules = NotificationRule::query()
            ->where('event', $event)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $ticket->category_id))
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

            $entry['user']->notify(new TicketEventNotification($ticket, $event, $actor, $payload, $channels));
        }
    }
}
