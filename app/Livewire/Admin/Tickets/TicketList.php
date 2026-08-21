<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\Group;
use App\Models\Issue;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketList extends Component
{
    use WithPagination;

    public string $ticketTypeFilter = '';

    public string $statusFilter = '';

    public string $categoryFilter = '';

    public string $issueFilter = '';

    public string $groupFilter = '';

    public string $assigneeFilter = '';

    public string $slaFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $retailerFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);
    }

    public function updating(string $name): void
    {
        if ($name === 'categoryFilter') {
            $this->issueFilter = '';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'ticketTypeFilter', 'statusFilter', 'categoryFilter', 'issueFilter',
            'groupFilter', 'assigneeFilter', 'slaFilter', 'dateFrom', 'dateTo', 'retailerFilter',
        ]);
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->scopedQuery()
            ->with(['ticketType', 'category', 'issue', 'assignee', 'relatedToGroup'])
            ->latest('tickets.created_at')
            ->get();

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Type', 'Category', 'Issue', 'Status', 'Priority', 'SLA', 'Related to', 'Assignee', 'Header', 'Created'], ',', '"', '\\');

            foreach ($tickets as $ticket) {
                fputcsv($out, [
                    $ticket->ticket_number,
                    $ticket->ticketType->name,
                    $ticket->category->name,
                    $ticket->issue->name,
                    $ticket->status,
                    $ticket->priority,
                    $ticket->slaStatus(),
                    $ticket->relatedToGroup?->name,
                    $ticket->assignee?->name,
                    $ticket->headerTitle(),
                    $ticket->created_at->format('Y-m-d H:i'),
                ], ',', '"', '\\');
            }

            fclose($out);
        }, 'tickets-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Query scoped by the current user's visibility (own/group/all) and by
     * every active filter — shared by the paginated list and the CSV export
     * so they can never drift apart.
     *
     * @return Builder<Ticket>
     */
    protected function scopedQuery(): Builder
    {
        $user = Auth::user();

        return Ticket::query()
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
            ->when($this->issueFilter, fn ($q) => $q->where('issue_id', $this->issueFilter))
            ->when($this->groupFilter, fn ($q) => $q->where('related_to_group_id', $this->groupFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->where('assignee_id', $this->assigneeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->retailerFilter, fn ($q) => $q->where('header->retailer_code', 'like', '%'.$this->retailerFilter.'%'))
            ->when($this->slaFilter, function ($query) {
                $query->select('tickets.*')->join('categories', 'categories.id', '=', 'tickets.category_id');

                if ($this->slaFilter === 'done') {
                    $query->whereIn('tickets.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);

                    return;
                }

                $query->whereNotIn('tickets.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
                $elapsedDays = 'TIMESTAMPDIFF(DAY, tickets.sla_status_since, NOW())';

                match ($this->slaFilter) {
                    'red' => $query->whereRaw("{$elapsedDays} >= categories.sla_red_days"),
                    'yellow' => $query->whereRaw("{$elapsedDays} >= categories.sla_yellow_days")
                        ->whereRaw("{$elapsedDays} < categories.sla_red_days"),
                    'green' => $query->whereRaw("{$elapsedDays} < categories.sla_yellow_days"),
                    default => null,
                };
            });
    }

    public function render(): View
    {
        $tickets = $this->scopedQuery()
            ->with(['ticketType', 'category', 'issue', 'assignee', 'relatedToGroup'])
            ->latest('tickets.created_at')
            ->paginate(15);

        return view('livewire.admin.tickets.ticket-list', [
            'tickets' => $tickets,
            'categories' => Category::orderBy('name')->get(),
            'issues' => $this->categoryFilter
                ? Issue::where('category_id', $this->categoryFilter)->orderBy('name')->get()
                : Issue::orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
