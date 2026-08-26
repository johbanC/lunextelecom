<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\TicketNotifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Genera / limpia tickets genéricos con estatus, prioridad y antigüedad de
 * SLA variados, para mostrarle a un cliente cómo se ve la plataforma con
 * datos poblados durante una demo de MVP. Bloqueado fuera de local/staging
 * — ver self::allowed() — y detrás del permiso catalog.manage, así que no
 * hay forma de dispararlo por accidente en producción ni sin ser Admin.
 */
class DemoDataGenerator extends Component
{
    public int $count = 20;

    protected static array $firstNames = [
        'James', 'Maria', 'Carlos', 'Linda', 'Robert', 'Patricia', 'Michael', 'Jennifer',
        'David', 'Sofia', 'John', 'Ana', 'Daniel', 'Laura', 'Kevin', 'Michelle',
        'Brian', 'Karen', 'Steven', 'Diana',
    ];

    protected static array $lastNames = [
        'Smith', 'Johnson', 'Garcia', 'Rodriguez', 'Williams', 'Brown', 'Martinez',
        'Davis', 'Lopez', 'Wilson', 'Anderson', 'Taylor', 'Hernandez', 'Moore', 'Jackson',
    ];

    protected static array $cities = ['Atlanta', 'Miami', 'Orlando', 'Charlotte', 'Nashville', 'Tampa', 'Savannah'];

    protected static array $states = ['GA', 'FL', 'NC', 'TN', 'SC'];

    public static function allowed(): bool
    {
        return app()->environment(['local', 'staging', 'demo']);
    }

    public function mount(): void
    {
        abort_unless(self::allowed(), 404);
        $this->authorize('catalog.manage');
    }

    public function generate(): void
    {
        abort_unless(self::allowed(), 404);
        $this->authorize('catalog.manage');

        $this->validate(['count' => ['required', 'integer', 'min:1', 'max:200']]);

        $ticketTypes = TicketType::all();
        $categories = Category::with('issues')->where('is_active', true)->get()->groupBy('ticket_type_id');
        $users = User::all();
        $groups = Group::where('is_active', true)->get();

        if ($users->isEmpty() || $categories->isEmpty()) {
            $this->addError('count', __('No catalog data or users to generate demo tickets from.'));

            return;
        }

        $statuses = array_keys(Ticket::STATUSES);
        $priorities = array_keys(Ticket::PRIORITIES);
        $slaBuckets = ['green', 'yellow', 'red'];

        $currentUser = Auth::user();
        $otherUsers = $users->reject(fn (User $u) => $currentUser && $u->is($currentUser))->values();

        for ($i = 0; $i < $this->count; $i++) {
            $type = $ticketTypes->random();
            $typeCategories = $categories->get($type->id, collect())->filter(fn (Category $c) => $c->issues->isNotEmpty());

            if ($typeCategories->isEmpty()) {
                continue;
            }

            $category = $typeCategories->random();
            $issue = $category->issues->random();

            $isDraft = $this->count >= 6 && $i % 6 === 5;
            $status = $isDraft ? Ticket::STATUS_NEW : $statuses[$i % count($statuses)];
            $priority = $priorities[$i % count($priorities)];

            // La mitad de los tickets no-borrador quedan asignados a quien está
            // generando la demo (con otro usuario como creador, para que la
            // notificación no se descarte por ser el mismo actor) — así el
            // presentador ve la campana poblada en vez de depender del azar.
            $notifyCurrentUser = ! $isDraft && $otherUsers->isNotEmpty() && $i % 2 === 0;

            $creator = $notifyCurrentUser ? $otherUsers->random() : $users->random();
            $assignee = $isDraft
                ? null
                : ($notifyCurrentUser ? $currentUser : (random_int(0, 4) > 0 ? $users->random() : null));
            $group = $groups->isNotEmpty() ? $groups->random() : null;

            $bucket = $status === Ticket::STATUS_RESOLVED ? 'done' : $slaBuckets[$i % count($slaBuckets)];
            $daysAgo = match ($bucket) {
                'green' => random_int(0, max(0, $category->sla_yellow_days - 1)),
                'yellow' => random_int($category->sla_yellow_days, max($category->sla_yellow_days, $category->sla_red_days - 1)),
                'red' => random_int($category->sla_red_days, $category->sla_red_days + 10),
                default => random_int(0, 20),
            };
            $slaStatusSince = now()->subDays($daysAgo)->subHours(random_int(0, 23));
            $createdAt = $slaStatusSince->copy()->subHours(random_int(0, 5));

            $header = $type->code === TicketType::RETAILER
                ? ['retailer_code' => strtoupper(Str::random(3)).'-'.random_int(1000, 9999), 'sku' => '']
                : [
                    'phone' => $this->fakePhone(),
                    'sku' => '',
                    'full_name' => $this->fakeName(),
                    'city' => Arr::random(self::$cities),
                    'state' => Arr::random(self::$states),
                    'tx_id' => '',
                ];

            $ticket = Ticket::create([
                'ticket_type_id' => $type->id,
                'category_id' => $category->id,
                'issue_id' => $issue->id,
                'header' => $header,
                'status' => $status,
                'is_draft' => $isDraft,
                'is_demo' => true,
                'priority' => $priority,
                'related_to_group_id' => $group?->id,
                'assignee_id' => $assignee?->id,
                'created_by' => $creator->id,
                'sla_status_since' => $slaStatusSince,
                'resolved_at' => $status === Ticket::STATUS_RESOLVED ? $slaStatusSince : null,
            ]);

            $ticket->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

            if (! $isDraft) {
                $ticket->events()->create([
                    'user_id' => $creator->id,
                    'type' => 'created',
                    'payload' => ['status' => $ticket->status, 'priority' => $ticket->priority],
                ]);

                TicketNotifier::notify(
                    $ticket,
                    TicketEventNotification::EVENT_CREATED,
                    $creator,
                    ['status' => $ticket->status, 'priority' => $ticket->priority],
                    directAssigneeId: $ticket->assignee_id,
                );
            }
        }

        session()->flash('status', __(':count demo tickets generated.', ['count' => $this->count]));
    }

    public function clear(): void
    {
        abort_unless(self::allowed(), 404);
        $this->authorize('catalog.manage');

        $demoTicketIds = Ticket::where('is_demo', true)->pluck('id');

        DB::table('notifications')->whereIn('data->ticket_id', $demoTicketIds)->delete();

        Ticket::where('is_demo', true)->get()->each(function (Ticket $ticket) {
            foreach ($ticket->attachments as $attachment) {
                Storage::disk('local')->delete($attachment->path);
            }
            $ticket->attachments()->delete();
            $ticket->fieldValues()->delete();
            $ticket->extraCustomers()->delete();
            $ticket->comments()->delete();
            $ticket->events()->delete();
            $ticket->delete();
        });

        session()->flash('status', __('Demo tickets cleared.'));
    }

    protected function fakeName(): string
    {
        return Arr::random(self::$firstNames).' '.Arr::random(self::$lastNames);
    }

    protected function fakePhone(): string
    {
        return sprintf('+1 (%03d) %03d-%04d', random_int(200, 999), random_int(200, 999), random_int(0, 9999));
    }

    public function render(): View
    {
        return view('livewire.admin.tickets.demo-data-generator', [
            'demoCount' => Ticket::where('is_demo', true)->count(),
        ]);
    }
}
