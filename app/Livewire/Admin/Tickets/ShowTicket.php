<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class ShowTicket extends Component
{
    public Ticket $ticket;

    public string $newComment = '';

    public string $commentVisibility = 'internal';

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    public function updateStatus(string $status): void
    {
        if (! in_array($status, [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
            return;
        }

        $previous = $this->ticket->status;
        if ($previous === $status) {
            return;
        }

        $this->ticket->update([
            'status' => $status,
            'sla_status_since' => now(),
            'resolved_at' => $status === Ticket::STATUS_RESOLVED ? now() : $this->ticket->resolved_at,
            'closed_at' => $status === Ticket::STATUS_CLOSED ? now() : $this->ticket->closed_at,
        ]);

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'status_changed',
            'payload' => ['from' => $previous, 'to' => $status],
        ]);

        $this->ticket->refresh();
    }

    public function reassign(?int $assigneeId): void
    {
        $previous = $this->ticket->assignee_id;
        if ($previous === $assigneeId) {
            return;
        }

        $this->ticket->update(['assignee_id' => $assigneeId]);

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => $previous ? 'reassigned' : 'assigned',
            'payload' => ['from' => $previous, 'to' => $assigneeId],
        ]);

        $this->ticket->refresh();
    }

    public function updateGroup(?int $groupId): void
    {
        $previous = $this->ticket->related_to_group_id;
        if ($previous === $groupId) {
            return;
        }

        $this->ticket->update(['related_to_group_id' => $groupId]);

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'reassigned',
            'payload' => ['group_from' => $previous, 'group_to' => $groupId],
        ]);

        $this->ticket->refresh();
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => ['required', 'string', 'max:5000'],
            'commentVisibility' => ['required', 'in:internal,external'],
        ]);

        $this->ticket->comments()->create([
            'user_id' => Auth::id(),
            'body' => $this->newComment,
            'visibility' => $this->commentVisibility,
        ]);

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'comment_added',
        ]);

        $this->newComment = '';
        $this->ticket->refresh();
    }

    public function render(): View
    {
        $this->ticket->load([
            'ticketType', 'category', 'issue', 'assignee', 'creator', 'relatedToGroup',
            'extraCustomers', 'fieldValues.fieldDefinition', 'events.user', 'comments.user',
        ]);

        $groups = Group::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Group $group) => $group->appliesTo($this->ticket->ticketType->code));

        return view('livewire.admin.tickets.show-ticket', [
            'groups' => $groups,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
