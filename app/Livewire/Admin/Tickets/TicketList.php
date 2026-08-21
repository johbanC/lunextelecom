<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    public string $ticketTypeFilter = '';

    public string $statusFilter = '';

    public string $categoryFilter = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $tickets = Ticket::query()
            ->with(['ticketType', 'category', 'issue', 'assignee', 'relatedToGroup'])
            ->when($this->ticketTypeFilter, fn ($q) => $q->whereHas('ticketType', fn ($t) => $t->where('code', $this->ticketTypeFilter)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.tickets.ticket-list', [
            'tickets' => $tickets,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
