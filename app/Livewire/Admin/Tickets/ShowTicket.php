<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\FieldDefinition;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\TicketNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ShowTicket extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public string $newComment = '';

    public string $commentVisibility = 'internal';

    /** @var array<int, TemporaryUploadedFile> */
    public array $newAttachments = [];

    public bool $showFieldsForm = false;

    /** @var array<int, string|array<int, string>> */
    public array $fieldValuesForm = [];

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket;
    }

    public function updateStatus(string $status): void
    {
        $this->authorize('changeStatus', $this->ticket);

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
            'sla_warning_notified_at' => null,
            'sla_breached_notified_at' => null,
            'resolved_at' => $status === Ticket::STATUS_RESOLVED ? now() : $this->ticket->resolved_at,
            'closed_at' => $status === Ticket::STATUS_CLOSED ? now() : $this->ticket->closed_at,
        ]);

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'status_changed',
            'payload' => ['from' => $previous, 'to' => $status],
        ]);

        TicketNotifier::notify(
            $this->ticket,
            TicketEventNotification::EVENT_STATUS_CHANGED,
            Auth::user(),
            ['from' => $previous, 'to' => $status],
            directAssigneeId: $this->ticket->assignee_id,
        );

        $this->ticket->refresh();
    }

    public function reassign(?int $assigneeId): void
    {
        $this->authorize('reassign', $this->ticket);

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

        TicketNotifier::notify(
            $this->ticket,
            TicketEventNotification::EVENT_REASSIGNED,
            Auth::user(),
            ['from' => $previous, 'to' => $assigneeId, 'assignee_name' => $assigneeId ? User::find($assigneeId)?->name : null],
            directAssigneeId: $assigneeId,
        );

        $this->ticket->refresh();
    }

    public function updateGroup(?int $groupId): void
    {
        $this->authorize('reassign', $this->ticket);

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

        TicketNotifier::notify(
            $this->ticket,
            TicketEventNotification::EVENT_REASSIGNED,
            Auth::user(),
            ['group_from' => $previous, 'group_to' => $groupId, 'assignee_name' => $this->ticket->assignee?->name],
        );

        $this->ticket->refresh();
    }

    public function addComment(): void
    {
        $this->authorize('comment', $this->ticket);

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

        if ($this->commentVisibility === 'external') {
            TicketNotifier::notify(
                $this->ticket,
                TicketEventNotification::EVENT_COMMENT_ADDED,
                Auth::user(),
                ['comment' => $this->newComment],
                directAssigneeId: $this->ticket->assignee_id,
            );
        }

        $this->newComment = '';
        $this->ticket->refresh();
    }

    public function uploadAttachments(): void
    {
        $this->authorize('attach', $this->ticket);

        $this->validate([
            'newAttachments' => ['required', 'array', 'max:5'],
            'newAttachments.*' => ['file', 'max:10240'],
        ]);

        foreach ($this->newAttachments as $file) {
            $path = $file->store('attachments/'.$this->ticket->id, 'local');

            $this->ticket->attachments()->create([
                'uploaded_by' => Auth::id(),
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'attachment_added',
            'payload' => ['count' => count($this->newAttachments)],
        ]);

        $this->newAttachments = [];
        $this->ticket->refresh();
    }

    public function updatedFieldValuesForm(mixed $value, string $key): void
    {
        $this->resetErrorBag("fieldValuesForm.{$key}");
    }

    public function editFields(): void
    {
        $this->authorize('editFields', $this->ticket);

        $this->ticket->load('issue.fieldDefinitions.options', 'fieldValues');
        $existing = $this->ticket->fieldValues->keyBy('field_definition_id');

        $this->fieldValuesForm = [];
        foreach ($this->ticket->issue->fieldDefinitions as $field) {
            $value = $existing->get($field->id)?->decodedValue();
            $this->fieldValuesForm[$field->id] = $field->isMultiValue() ? ($value ?? []) : ($value ?? '');
        }

        $this->showFieldsForm = true;
    }

    public function saveFields(): void
    {
        $this->authorize('editFields', $this->ticket);

        $issue = $this->ticket->issue()->with('fieldDefinitions')->first();

        $rules = [];
        foreach ($issue->fieldDefinitions as $field) {
            $required = $field->is_required ? 'required' : 'nullable';
            $rules["fieldValuesForm.{$field->id}"] = $field->isMultiValue() ? [$required, 'array'] : [$required, 'string', 'max:2000'];
        }
        $this->validate($rules);

        foreach ($issue->fieldDefinitions as $field) {
            if ($field->field_type === FieldDefinition::TYPE_PICK_N && $field->pick_count) {
                $selected = $this->fieldValuesForm[$field->id] ?? [];
                if (count($selected) !== $field->pick_count) {
                    $this->addError("fieldValuesForm.{$field->id}", __('Select exactly :n option(s) for :label.', ['n' => $field->pick_count, 'label' => $field->label]));

                    return;
                }
            }
        }

        $changed = [];

        foreach ($issue->fieldDefinitions as $field) {
            $rawValue = $this->fieldValuesForm[$field->id] ?? ($field->isMultiValue() ? [] : '');
            $isEmpty = $rawValue === '' || $rawValue === [] || $rawValue === null;
            $newStored = $isEmpty ? null : ($field->isMultiValue() ? json_encode(array_values((array) $rawValue)) : $rawValue);

            $existingRow = $this->ticket->fieldValues->firstWhere('field_definition_id', $field->id);
            $oldStored = $existingRow?->value;

            if ($newStored === $oldStored) {
                continue;
            }

            if ($newStored === null) {
                $existingRow?->delete();
            } else {
                $this->ticket->fieldValues()->updateOrCreate(
                    ['field_definition_id' => $field->id],
                    ['value' => $newStored]
                );
            }

            $changed[] = [
                'label' => $field->label,
                'from' => $this->formatStoredFieldValue($oldStored, $field),
                'to' => $this->formatStoredFieldValue($newStored, $field),
            ];
        }

        if (! empty($changed)) {
            $this->ticket->events()->create([
                'user_id' => Auth::id(),
                'type' => 'fields_updated',
                'payload' => ['fields' => $changed],
            ]);
        }

        $this->showFieldsForm = false;
        $this->ticket->refresh();
    }

    protected function formatStoredFieldValue(?string $stored, FieldDefinition $field): ?string
    {
        if ($stored === null) {
            return null;
        }

        if ($field->isMultiValue()) {
            return implode(', ', json_decode($stored, true) ?? []);
        }

        return $stored;
    }

    public function render(): View
    {
        $this->ticket->load([
            'ticketType', 'category', 'issue', 'assignee', 'creator', 'relatedToGroup',
            'extraCustomers', 'fieldValues.fieldDefinition', 'events.user', 'comments.user',
            'attachments.uploader',
        ]);

        $groups = Group::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Group $group) => $group->appliesTo($this->ticket->ticketType->code));

        return view('livewire.admin.tickets.show-ticket', [
            'groups' => $groups,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
