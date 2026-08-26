<?php

namespace App\Livewire\Admin;

use App\Models\EmailLog;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Auditoría de correos enviados por la plataforma (tickets + formularios
 * firmados) — quién los recibió, con qué asunto/finalidad, si se enviaron
 * o fallaron, y si el destinatario los abrió (píxel de seguimiento en
 * resources/views/emails/branded.blade.php). Ver app/Models/EmailLog.php.
 */
class EmailLogList extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $openedFilter = '';

    public string $eventFilter = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('email_log.view');
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['statusFilter', 'openedFilter', 'eventFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'openedFilter', 'eventFilter', 'search']);
        $this->resetPage();
    }

    /**
     * @return Builder<EmailLog>
     */
    protected function scopedQuery(): Builder
    {
        return EmailLog::query()
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->eventFilter, fn ($q) => $q->where('event', $this->eventFilter))
            ->when($this->openedFilter === 'yes', fn ($q) => $q->whereNotNull('opened_at'))
            ->when($this->openedFilter === 'no', fn ($q) => $q->whereNull('opened_at'))
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($q) use ($term) {
                    $q->where('to_email', 'like', $term)
                        ->orWhere('to_name', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'like', $term));
                });
            });
    }

    public function render(): View
    {
        $logs = $this->scopedQuery()
            ->with(['ticket', 'agreement', 'user'])
            ->latest()
            ->paginate(20);

        $base = EmailLog::query();

        return view('livewire.admin.email-log-list', [
            'logs' => $logs,
            'totalSent' => (clone $base)->where('status', 'sent')->count(),
            'totalFailed' => (clone $base)->where('status', 'failed')->count(),
            'totalOpened' => (clone $base)->whereNotNull('opened_at')->count(),
            'events' => EmailLog::query()->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
