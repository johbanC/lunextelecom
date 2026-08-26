<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\FieldDefinition;
use App\Models\Group;
use App\Models\Issue;
use App\Models\Retailer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\TicketNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CreateTicket extends Component
{
    use WithFileUploads;

    public string $ticketTypeCode = TicketType::RETAILER;

    public ?int $categoryId = null;

    public ?int $issueId = null;

    /** @var array<string, mixed> */
    public array $header = [];

    /** @var array<int, string|array<int, string>> */
    public array $fieldValues = [];

    public string $priority = 'normal';

    public string $status = Ticket::STATUS_NEW;

    public ?int $relatedToGroupId = null;

    public ?int $assigneeId = null;

    /** @var array<int, array{full_name: string, phone: string}> */
    public array $extraCustomers = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $newAttachments = [];

    public ?Ticket $editingDraft = null;

    public function mount(?Ticket $draft = null): void
    {
        $this->authorize('create', Ticket::class);

        if (! $draft) {
            $this->resetHeader();

            return;
        }

        abort_unless($draft->is_draft, 404);
        $this->authorize('view', $draft);

        $this->editingDraft = $draft;
        $this->ticketTypeCode = $draft->ticketType->code;
        $this->categoryId = $draft->category_id;
        $this->issueId = $draft->issue_id;
        $this->priority = $draft->priority;
        $this->status = $draft->status;
        $this->relatedToGroupId = $draft->related_to_group_id;
        $this->assigneeId = $draft->assignee_id;
        $this->resetHeader($draft->header ?? []);

        foreach ($draft->fieldValues()->with('fieldDefinition')->get() as $fieldValue) {
            $this->fieldValues[$fieldValue->field_definition_id] = $fieldValue->decodedValue() ?? ($fieldValue->fieldDefinition?->isMultiValue() ? [] : '');
        }

        $this->extraCustomers = $draft->extraCustomers
            ->map(fn ($extra) => ['full_name' => $extra->full_name, 'phone' => $extra->phone])
            ->values()
            ->all();
    }

    public function selectType(string $code): void
    {
        $this->ticketTypeCode = $code;
        $this->categoryId = null;
        $this->issueId = null;
        $this->fieldValues = [];
        $this->extraCustomers = [];
        $this->resetHeader();
    }

    public function updatedCategoryId(): void
    {
        $this->issueId = null;
        $this->fieldValues = [];

        $category = $this->categoryId ? Category::find($this->categoryId) : null;
        $this->relatedToGroupId = $category?->default_related_to_group_id;
    }

    public function updatedIssueId(): void
    {
        $this->fieldValues = [];

        if (! $this->issueId) {
            return;
        }

        foreach ($this->currentIssue()?->fieldDefinitions ?? [] as $field) {
            $this->fieldValues[$field->id] = $field->isMultiValue() ? [] : '';
        }
    }

    public function updatedFieldValues(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            $this->resetErrorBag();

            return;
        }

        $this->resetErrorBag("fieldValues.{$key}");
    }

    public function addExtraCustomer(): void
    {
        $this->extraCustomers[] = ['full_name' => '', 'phone' => ''];
    }

    public function removeExtraCustomer(int $index): void
    {
        unset($this->extraCustomers[$index]);
        $this->extraCustomers = array_values($this->extraCustomers);
    }

    /**
     * @param  array<string, mixed>  $existing  Valores a conservar (al reanudar un borrador).
     */
    protected function resetHeader(array $existing = []): void
    {
        $keys = $this->ticketTypeCode === TicketType::RETAILER
            ? ['retailer_code', 'sku']
            : ['phone', 'sku', 'full_name', 'city', 'state', 'tx_id'];

        $this->header = array_merge(array_fill_keys($keys, ''), array_intersect_key($existing, array_flip($keys)));
    }

    protected function currentIssue(): ?Issue
    {
        return $this->issueId ? Issue::with('fieldDefinitions.options')->find($this->issueId) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'categoryId' => ['required', 'exists:categories,id'],
            'issueId' => ['required', 'exists:issues,id'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'status' => ['required', 'in:new,processing,follow_up,resolved,informational'],
            'relatedToGroupId' => ['nullable', 'exists:groups,id'],
            'assigneeId' => ['nullable', 'exists:users,id'],
        ];

        $headerLabels = $this->ticketTypeCode === TicketType::RETAILER
            ? ['retailer_code' => 'required']
            : ['phone' => 'required', 'full_name' => 'required', 'tx_id' => 'required'];

        foreach (array_keys($this->header) as $key) {
            $rules["header.{$key}"] = isset($headerLabels[$key]) ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
        }

        foreach ($this->currentIssue()?->fieldDefinitions ?? [] as $field) {
            $required = $field->is_required ? 'required' : 'nullable';
            $rules["fieldValues.{$field->id}"] = $field->isMultiValue() ? [$required, 'array'] : [$required, 'string', 'max:2000'];
        }

        if ($this->ticketTypeCode === TicketType::CUSTOMER) {
            $rules['extraCustomers.*.full_name'] = ['required', 'string', 'max:255'];
            $rules['extraCustomers.*.phone'] = ['nullable', 'string', 'max:50'];
        }

        $rules['newAttachments'] = ['nullable', 'array', 'max:5'];
        $rules['newAttachments.*'] = ['file', 'max:10240'];

        return $rules;
    }

    /**
     * Guarda datos incompletos como borrador: solo Category/Issue son
     * obligatorios (para saber qué campos dinámicos aplican), el resto
     * queda pendiente hasta que se publique. Visible solo para quien lo
     * creó (y para Admin/Director) — ver TicketPolicy::view().
     */
    public function saveDraft(): void
    {
        $this->authorize('create', Ticket::class);

        $this->validate([
            'categoryId' => ['required', 'exists:categories,id'],
            'issueId' => ['required', 'exists:issues,id'],
        ]);

        if (filled($this->header['retailer_code'] ?? null)) {
            $this->header['retailer_code'] = strtoupper($this->header['retailer_code']);
        }

        $ticketType = TicketType::where('code', $this->ticketTypeCode)->firstOrFail();
        $issue = $this->currentIssue();

        $attributes = [
            'ticket_type_id' => $ticketType->id,
            'category_id' => $this->categoryId,
            'issue_id' => $this->issueId,
            'header' => $this->header,
            'status' => $this->status,
            'is_draft' => true,
            'priority' => $this->priority,
            'related_to_group_id' => $this->relatedToGroupId,
            'assignee_id' => $this->assigneeId,
        ];

        $ticket = DB::transaction(function () use ($attributes, $issue) {
            $ticket = $this->editingDraft
                ? tap($this->editingDraft)->update($attributes)
                : Ticket::create($attributes + ['created_by' => Auth::id(), 'sla_status_since' => now()]);

            $this->syncFieldValues($ticket, $issue);
            $this->syncExtraCustomers($ticket);

            return $ticket;
        });

        $this->syncAttachments($ticket);

        session()->flash('status', __('Draft saved. Only you (and Admin/Director) can see it until you finish and create the ticket.'));

        $this->redirectRoute('admin.tickets.drafts.edit', $ticket, navigate: true);
    }

    public function save(): void
    {
        $this->authorize('create', Ticket::class);

        $this->validate();

        if (filled($this->header['retailer_code'] ?? null)) {
            $this->header['retailer_code'] = strtoupper($this->header['retailer_code']);
        }

        $issue = $this->currentIssue();

        foreach ($issue->fieldDefinitions as $field) {
            if ($field->field_type === FieldDefinition::TYPE_PICK_N && $field->pick_count) {
                $selected = $this->fieldValues[$field->id] ?? [];
                if (count($selected) < $field->pick_count) {
                    $this->addError("fieldValues.{$field->id}", __('Select at least :n option(s) for :label.', ['n' => $field->pick_count, 'label' => $field->label]));

                    return;
                }
            }
        }

        $ticketType = TicketType::where('code', $this->ticketTypeCode)->firstOrFail();

        if ($this->ticketTypeCode === TicketType::RETAILER && filled($this->header['retailer_code'] ?? null)) {
            Retailer::firstOrCreate(['code' => $this->header['retailer_code']]);
        }

        $attributes = [
            'ticket_type_id' => $ticketType->id,
            'category_id' => $this->categoryId,
            'issue_id' => $this->issueId,
            'header' => $this->header,
            'status' => $this->status,
            'priority' => $this->priority,
            'related_to_group_id' => $this->relatedToGroupId,
            'assignee_id' => $this->assigneeId,
        ];

        $ticket = DB::transaction(function () use ($attributes, $issue) {
            if ($this->editingDraft) {
                $ticket = $this->editingDraft;
                $ticket->update($attributes);
                $ticket->publish();
            } else {
                $ticket = Ticket::create($attributes + ['created_by' => Auth::id(), 'sla_status_since' => now()]);
            }

            $this->syncFieldValues($ticket, $issue);
            $this->syncExtraCustomers($ticket);

            $ticket->events()->create([
                'user_id' => Auth::id(),
                'type' => 'created',
                'payload' => ['status' => $ticket->status, 'priority' => $ticket->priority],
            ]);

            return $ticket;
        });

        $this->syncAttachments($ticket);

        TicketNotifier::notify(
            $ticket,
            TicketEventNotification::EVENT_CREATED,
            Auth::user(),
            ['status' => $ticket->status, 'priority' => $ticket->priority],
            directAssigneeId: $ticket->assignee_id,
            notifyRelatedGroup: true,
        );

        session()->flash('status', __('Ticket :number created.', ['number' => $ticket->ticket_number]));

        $this->redirectRoute('admin.tickets.show', $ticket, navigate: true);
    }

    protected function syncFieldValues(Ticket $ticket, ?Issue $issue): void
    {
        $ticket->fieldValues()->delete();

        foreach ($issue?->fieldDefinitions ?? [] as $field) {
            $value = $this->fieldValues[$field->id] ?? null;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $ticket->fieldValues()->create([
                'field_definition_id' => $field->id,
                'value' => $field->isMultiValue() ? json_encode(array_values($value)) : $value,
            ]);
        }
    }

    protected function syncExtraCustomers(Ticket $ticket): void
    {
        $ticket->extraCustomers()->delete();

        foreach ($this->extraCustomers as $extra) {
            if (! empty($extra['full_name'])) {
                $ticket->extraCustomers()->create($extra);
            }
        }
    }

    protected function syncAttachments(Ticket $ticket): void
    {
        foreach ($this->newAttachments as $file) {
            $path = $file->store('attachments/'.$ticket->id, 'local');

            $ticket->attachments()->create([
                'uploaded_by' => Auth::id(),
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->newAttachments = [];
    }

    public function render(): View
    {
        $ticketTypes = TicketType::orderBy('code')->get();

        $categories = Category::whereHas('ticketType', fn ($q) => $q->where('code', $this->ticketTypeCode))
            ->orderBy('sort_order')
            ->get();

        $issues = $this->categoryId
            ? Issue::where('category_id', $this->categoryId)->orderBy('sort_order')->get()
            : collect();

        $groups = Group::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Group $group) => $group->appliesTo($this->ticketTypeCode));

        return view('livewire.admin.tickets.create-ticket', [
            'ticketTypes' => $ticketTypes,
            'categories' => $categories,
            'issues' => $issues,
            'groups' => $groups,
            'users' => User::orderBy('name')->get(),
            'selectedIssue' => $this->currentIssue(),
            'retailerCodes' => $this->ticketTypeCode === TicketType::RETAILER
                ? Retailer::orderByDesc('id')->limit(200)->pluck('code')
                : collect(),
        ]);
    }
}
