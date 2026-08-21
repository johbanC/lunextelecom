<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Notifications\TicketEventNotification;
use App\Services\TicketNotifier;
use Illuminate\Console\Command;

/**
 * Recorre los tickets abiertos y dispara sla_warning / sla_breached cuando
 * cruzan los umbrales amarillo/rojo de su categoría — docs/SPEC_DESARROLLO.md
 * sección 8.1. Cada nivel se notifica una sola vez por ciclo de SLA (se
 * resetea al cambiar de estado, ver ShowTicket::updateStatus) para no
 * reenviar el mismo aviso en cada corrida del scheduler.
 */
class CheckTicketSlas extends Command
{
    protected $signature = 'tickets:check-slas';

    protected $description = 'Send sla_warning/sla_breached notifications for tickets past their category SLA thresholds';

    public function handle(): int
    {
        $tickets = Ticket::query()
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->with('category')
            ->get();

        $warned = 0;
        $breached = 0;

        foreach ($tickets as $ticket) {
            $status = $ticket->slaStatus();

            if ($status === 'red' && ! $ticket->sla_breached_notified_at) {
                TicketNotifier::notify($ticket, TicketEventNotification::EVENT_SLA_BREACHED, null, [
                    'sla_days' => $ticket->category->sla_red_days,
                ], directAssigneeId: $ticket->assignee_id);
                $ticket->update(['sla_breached_notified_at' => now()]);
                $breached++;

                continue;
            }

            if ($status === 'yellow' && ! $ticket->sla_warning_notified_at) {
                TicketNotifier::notify($ticket, TicketEventNotification::EVENT_SLA_WARNING, null, [
                    'sla_days' => $ticket->category->sla_yellow_days,
                ], directAssigneeId: $ticket->assignee_id);
                $ticket->update(['sla_warning_notified_at' => now()]);
                $warned++;
            }
        }

        $this->info("SLA check complete: {$warned} warning(s), {$breached} breach(es) notified.");

        return self::SUCCESS;
    }
}
