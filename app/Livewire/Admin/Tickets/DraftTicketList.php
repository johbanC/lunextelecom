<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Ticket;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * "Mis borradores" — tickets guardados sin completar (ver TicketPolicy::view
 * para la regla de visibilidad: el creador y quien tenga tickets.view.all).
 */
class DraftTicketList extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('create', Ticket::class);
    }

    public function discard(int $ticketId): void
    {
        $ticket = Ticket::where('is_draft', true)->findOrFail($ticketId);

        $this->authorize('view', $ticket);

        $ticket->fieldValues()->delete();
        $ticket->extraCustomers()->delete();

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->path);
        }
        $ticket->attachments()->delete();

        $ticket->delete();
    }

    /**
     * @return Builder<Ticket>
     */
    protected function scopedQuery(): Builder
    {
        $user = Auth::user();

        return Ticket::query()
            ->where('is_draft', true)
            ->when(! $user->can('tickets.view.all'), fn ($q) => $q->where('created_by', $user->id));
    }

    public function render(): View
    {
        $drafts = $this->scopedQuery()
            ->with(['ticketType', 'category', 'issue', 'creator'])
            ->latest('updated_at')
            ->paginate(15);

        return view('livewire.admin.tickets.draft-ticket-list', [
            'drafts' => $drafts,
        ]);
    }
}
