<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes por equipo (Líder) o globales (Director/Admin) —
 * docs/SPEC_DESARROLLO.md sección 7 (reports.view.group / reports.view.all)
 * y sección 8.8 ("filtrar y descargar información de tickets").
 */
class ReportsDashboard extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $ticketTypeFilter = '';

    public string $categoryFilter = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user->can('reports.view.group') || $user->can('reports.view.all'), 403);

        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function exportCsv(): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user->can('reports.export'), 403);

        $tickets = $this->scopedQuery()
            ->with(['ticketType', 'category', 'issue', 'assignee', 'relatedToGroup'])
            ->latest('tickets.created_at')
            ->get();

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Type', 'Category', 'Issue', 'Status', 'Priority', 'SLA', 'Related to', 'Assignee', 'Created', 'Resolved'], ',', '"', '\\');

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
                    $ticket->created_at->format('Y-m-d H:i'),
                    $ticket->resolved_at?->format('Y-m-d H:i'),
                ], ',', '"', '\\');
            }

            fclose($out);
        }, 'report-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Builder<Ticket>
     */
    protected function scopedQuery(): Builder
    {
        $user = Auth::user();

        return Ticket::query()
            ->when(! $user->can('reports.view.all'), function ($query) use ($user) {
                $query->whereIn('tickets.related_to_group_id', $user->groups()->pluck('groups.id'));
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('tickets.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('tickets.created_at', '<=', $this->dateTo))
            ->when($this->ticketTypeFilter, fn ($q) => $q->whereHas('ticketType', fn ($t) => $t->where('code', $this->ticketTypeFilter)))
            ->when($this->categoryFilter, fn ($q) => $q->where('tickets.category_id', $this->categoryFilter));
    }

    public function render(): View
    {
        $base = $this->scopedQuery();

        $total = (clone $base)->count();

        $byStatus = (clone $base)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $slaBreakdown = (clone $base)
            ->select('tickets.status', DB::raw('TIMESTAMPDIFF(DAY, tickets.sla_status_since, NOW()) as elapsed'), 'categories.sla_yellow_days', 'categories.sla_red_days')
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->get()
            ->countBy(function ($row) {
                if (in_array($row->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
                    return 'done';
                }
                if ($row->elapsed >= $row->sla_red_days) {
                    return 'red';
                }
                if ($row->elapsed >= $row->sla_yellow_days) {
                    return 'yellow';
                }

                return 'green';
            });

        $byCategory = (clone $base)
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->select('categories.name', DB::raw('count(*) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'name');

        $byAssignee = (clone $base)
            ->join('users', 'users.id', '=', 'tickets.assignee_id')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->pluck('total', 'name');

        $unassignedCount = (clone $base)->whereNull('assignee_id')->count();

        $avgResolutionHours = (clone $base)
            ->whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours');

        return view('livewire.admin.reports-dashboard', [
            'total' => $total,
            'byStatus' => $byStatus,
            'slaBreakdown' => $slaBreakdown,
            'byCategory' => $byCategory,
            'byAssignee' => $byAssignee,
            'unassignedCount' => $unassignedCount,
            'avgResolutionHours' => $avgResolutionHours,
            'categories' => Category::orderBy('name')->get(),
            'canExport' => Auth::user()->can('reports.export'),
        ]);
    }
}
