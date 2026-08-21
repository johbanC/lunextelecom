<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Category;
use App\Models\FieldDefinition;
use App\Models\Group;
use App\Models\Issue;
use App\Models\Retailer;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class CreateTicket extends Component
{
    public string $ticketTypeCode = TicketType::RETAILER;

    public ?int $categoryId = null;

    public ?int $issueId = null;

    /** @var array<string, mixed> */
    public array $header = [];

    /** @var array<int, string|array<int, string>> */
    public array $fieldValues = [];

    public string $priority = 'normal';

    public string $status = Ticket::STATUS_OPEN;

    public ?int $relatedToGroupId = null;

    public ?int $assigneeId = null;

    /** @var array<int, array{full_name: string, phone: string}> */
    public array $extraCustomers = [];

    public function mount(): void
    {
        $this->authorize('create', Ticket::class);

        $this->resetHeader();
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

    public function addExtraCustomer(): void
    {
        $this->extraCustomers[] = ['full_name' => '', 'phone' => ''];
    }

    public function removeExtraCustomer(int $index): void
    {
        unset($this->extraCustomers[$index]);
        $this->extraCustomers = array_values($this->extraCustomers);
    }

    protected function resetHeader(): void
    {
        $this->header = $this->ticketTypeCode === TicketType::RETAILER
            ? array_fill_keys(['retailer_code', 'sku'], '')
            : array_fill_keys(['phone', 'sku', 'full_name', 'city', 'state', 'tx_id'], '');
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
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
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

        return $rules;
    }

    public function save(): void
    {
        $this->authorize('create', Ticket::class);

        $this->validate();

        $issue = $this->currentIssue();

        foreach ($issue->fieldDefinitions as $field) {
            if ($field->field_type === FieldDefinition::TYPE_PICK_N && $field->pick_count) {
                $selected = $this->fieldValues[$field->id] ?? [];
                if (count($selected) !== $field->pick_count) {
                    $this->addError("fieldValues.{$field->id}", __('Select exactly :n option(s) for :label.', ['n' => $field->pick_count, 'label' => $field->label]));

                    return;
                }
            }
        }

        $ticketType = TicketType::where('code', $this->ticketTypeCode)->firstOrFail();

        if ($this->ticketTypeCode === TicketType::RETAILER && filled($this->header['retailer_code'] ?? null)) {
            Retailer::firstOrCreate(['code' => $this->header['retailer_code']]);
        }

        $ticket = DB::transaction(function () use ($issue, $ticketType) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'category_id' => $this->categoryId,
                'issue_id' => $this->issueId,
                'header' => $this->header,
                'status' => $this->status,
                'priority' => $this->priority,
                'related_to_group_id' => $this->relatedToGroupId,
                'assignee_id' => $this->assigneeId,
                'created_by' => Auth::id(),
                'sla_status_since' => now(),
            ]);

            foreach ($issue->fieldDefinitions as $field) {
                $value = $this->fieldValues[$field->id] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }

                $ticket->fieldValues()->create([
                    'field_definition_id' => $field->id,
                    'value' => $field->isMultiValue() ? json_encode(array_values($value)) : $value,
                ]);
            }

            foreach ($this->extraCustomers as $extra) {
                if (! empty($extra['full_name'])) {
                    $ticket->extraCustomers()->create($extra);
                }
            }

            $ticket->events()->create([
                'user_id' => Auth::id(),
                'type' => 'created',
                'payload' => ['status' => $ticket->status, 'priority' => $ticket->priority],
            ]);

            return $ticket;
        });

        session()->flash('status', __('Ticket :number created.', ['number' => $ticket->ticket_number]));

        $this->redirectRoute('admin.tickets.show', $ticket, navigate: true);
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
