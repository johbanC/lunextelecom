<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    public string $ticketTypeFilter = '';

    public string $statusFilter = '';

    public string $categoryFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = Auth::user();

        $tickets = Ticket::query()
            ->with(['ticketType', 'category', 'issue', 'assignee', 'relatedToGroup'])
            ->when(! $user->can('tickets.view.all'), function ($query) use ($user) {
                $query->where(function ($scope) use ($user) {
                    $scope->whereRaw('1 = 0');

                    if ($user->can('tickets.view.group')) {
                        $scope->orWhereIn('related_to_group_id', $user->groups()->pluck('groups.id'));
                    }

                    if ($user->can('tickets.view.own')) {
                        $scope->orWhere('created_by', $user->id)->orWhere('assignee_id', $user->id);
                    }
                });
            })
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
