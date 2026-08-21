<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-gray-800">{{ $ticket->ticket_number }}</h1>
                <x-sla-chip :status="$ticket->slaStatus()" />
            </div>
            <p class="text-sm text-gray-500 mt-1">{{ $ticket->ticketType->name }} &middot; {{ $ticket->category->name }} &middot; {{ $ticket->issue->name }}</p>
        </div>
        <a href="{{ route('admin.tickets.index') }}" wire:navigate class="text-sm font-semibold text-brand-blue hover:underline">&larr; {{ __('Back to list') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Encabezado --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Header') }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                    @foreach ($ticket->header as $key => $value)
                        @if ($value)
                            <div>
                                <div class="text-xs text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                                <div class="text-gray-700 font-medium">{{ $value }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($ticket->extraCustomers->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-xs text-gray-400 mb-2">{{ __('Extra customers') }}</div>
                        <ul class="space-y-1 text-sm text-gray-600">
                            @foreach ($ticket->extraCustomers as $extra)
                                <li>{{ $extra->full_name }} @if ($extra->phone) &middot; {{ $extra->phone }} @endif</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Campos dinámicos --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $ticket->issue->name }}</h2>
                    @can('editFields', $ticket)
                        <button wire:click="editFields" class="text-xs font-semibold text-brand-blue hover:underline">{{ __('Edit') }}</button>
                    @endcan
                </div>
                @if ($ticket->fieldValues->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('No extra fields recorded.') }}</p>
                @else
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        @foreach ($ticket->fieldValues as $fv)
                            <div>
                                <dt class="text-xs text-gray-400">{{ $fv->fieldDefinition->label }}</dt>
                                <dd class="text-gray-700 font-medium">
                                    @php($decoded = $fv->decodedValue())
                                    {{ is_array($decoded) ? implode(', ', $decoded) : $decoded }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Timeline') }}</h2>
                <ul class="space-y-3">
                    @foreach ($ticket->events as $event)
                        <li class="flex gap-3 text-sm">
                            <span class="size-2 mt-1.5 rounded-full bg-brand-blue shrink-0"></span>
                            <div>
                                <div class="text-gray-700">
                                    <span class="font-semibold">{{ $event->user?->name ?? __('System') }}</span>
                                    {{ $event->describe() }}
                                </div>
                                @if ($event->type === 'fields_updated')
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach ($event->fieldChanges() as $change)
                                            <li class="text-xs text-gray-500">
                                                <span class="font-medium text-gray-600">{{ $change['label'] }}:</span>
                                                <span class="line-through text-gray-400">{{ $change['from'] ?? __('empty') }}</span>
                                                &rarr;
                                                <span class="text-gray-700">{{ $change['to'] ?? __('empty') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="text-xs text-gray-400">{{ $event->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Adjuntos --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Attachments') }}</h2>
                <ul class="space-y-2 mb-4">
                    @forelse ($ticket->attachments as $attachment)
                        <li class="flex items-center justify-between gap-2 text-sm border border-gray-100 rounded-lg p-3">
                            <div class="min-w-0">
                                <a href="{{ route('admin.tickets.attachments.download', [$ticket, $attachment]) }}" class="font-medium text-brand-blue hover:underline truncate block">{{ $attachment->original_name }}</a>
                                <div class="text-xs text-gray-400">
                                    {{ number_format($attachment->size / 1024, 0) }} KB &middot;
                                    {{ __('uploaded by') }} {{ $attachment->uploader?->name ?? __('Unknown') }} &middot;
                                    {{ $attachment->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">{{ __('No attachments yet.') }}</li>
                    @endforelse
                </ul>
                @can('attach', $ticket)
                    <form wire:submit="uploadAttachments" class="space-y-2">
                        <input type="file" wire:model="newAttachments" multiple
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-600 file:text-xs file:font-semibold hover:file:bg-gray-200">
                        @error('newAttachments') <p class="text-xs text-brand-red">{{ $message }}</p> @enderror
                        @error('newAttachments.*') <p class="text-xs text-brand-red">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="newAttachments" class="text-xs text-gray-400">{{ __('Uploading…') }}</div>
                        <button type="submit" class="bg-brand-blue text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">
                            {{ __('Upload') }}
                        </button>
                    </form>
                @endcan
            </div>

            {{-- Comentarios --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Comments') }}</h2>
                <ul class="space-y-3 mb-4">
                    @forelse ($ticket->comments as $comment)
                        <li class="text-sm border border-gray-100 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-gray-700">{{ $comment->user->name }}</span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-gray-600 mt-1">{{ $comment->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">{{ __('No comments yet.') }}</li>
                    @endforelse
                </ul>
                @can('comment', $ticket)
                    <form wire:submit="addComment" class="space-y-2">
                        <textarea wire:model="newComment" rows="3" placeholder="{{ __('Write a comment…') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                        @error('newComment') <p class="text-xs text-brand-red">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-between">
                            <select wire:model="commentVisibility" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                <option value="internal">{{ __('Internal') }}</option>
                                <option value="external">{{ __('External') }}</option>
                            </select>
                            <button type="submit" class="bg-brand-blue text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">
                                {{ __('Comment') }}
                            </button>
                        </div>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Panel lateral: acciones --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Status') }}</h2>
                @can('changeStatus', $ticket)
                    <select wire:change="updateStatus($event.target.value)" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'resolved' => __('Resolved'), 'closed' => __('Closed')] as $value => $label)
                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="text-sm text-gray-700 font-medium capitalize">{{ str_replace('_', ' ', $ticket->status) }}</p>
                @endcan

                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mt-4 mb-1">{{ __('Priority') }}</h2>
                <p class="text-sm text-gray-700 font-medium capitalize">{{ $ticket->priority }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">{{ __('Related to') }}</h2>
                @can('reassign', $ticket)
                    <select wire:change="updateGroup($event.target.value ? $event.target.value : null)" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Unassigned group') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected($ticket->related_to_group_id === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>

                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mt-4 mb-1">{{ __('Assignee') }}</h2>
                    <select wire:change="reassign($event.target.value ? $event.target.value : null)" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($ticket->assignee_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="text-sm text-gray-700 font-medium">{{ $ticket->relatedToGroup?->name ?? __('Unassigned group') }}</p>

                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mt-4 mb-1">{{ __('Assignee') }}</h2>
                    <p class="text-sm text-gray-700 font-medium">{{ $ticket->assignee?->name ?? __('Unassigned') }}</p>
                @endcan
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 text-sm text-gray-500 space-y-1">
                <div>{{ __('Created by') }} <span class="font-medium text-gray-700">{{ $ticket->creator->name }}</span></div>
                <div>{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Modal: editar campos dinámicos --}}
    @if ($showFieldsForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showFieldsForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ __('Edit :issue fields', ['issue' => $ticket->issue->name]) }}</h2>
                <form wire:submit="saveFields" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($ticket->issue->fieldDefinitions as $field)
                            <div class="{{ in_array($field->field_type, ['textarea', 'checkbox', 'pick_n']) ? 'sm:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-gray-600 mb-1">
                                    {{ $field->label }}
                                    @if ($field->is_required) <span class="text-brand-red">*</span> @endif
                                    @if ($field->field_type === 'pick_n') <span class="text-xs text-gray-400">({{ __('Pick :n', ['n' => $field->pick_count]) }})</span> @endif
                                </label>

                                @switch($field->field_type)
                                    @case('textarea')
                                        <textarea wire:model="fieldValuesForm.{{ $field->id }}" rows="3"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                                        @break

                                    @case('date')
                                        <input type="date" wire:model="fieldValuesForm.{{ $field->id }}"
                                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                        @break

                                    @case('select')
                                        <select wire:model="fieldValuesForm.{{ $field->id }}"
                                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                            <option value="">{{ __('Select…') }}</option>
                                            @foreach ($field->options as $option)
                                                <option value="{{ $option->value }}">{{ $option->value }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('radio')
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 pt-1.5">
                                            @foreach ($field->options as $option)
                                                <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                    <input type="radio" wire:model="fieldValuesForm.{{ $field->id }}" value="{{ $option->value }}"
                                                        class="text-brand-blue focus:ring-brand-blue">
                                                    {{ $option->value }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @break

                                    @case('checkbox')
                                    @case('pick_n')
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 pt-1.5">
                                            @foreach ($field->options as $option)
                                                <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                                                    <input type="checkbox" wire:model="fieldValuesForm.{{ $field->id }}" value="{{ $option->value }}"
                                                        class="rounded text-brand-blue focus:ring-brand-blue">
                                                    {{ $option->value }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @break

                                    @default
                                        <input type="text" wire:model="fieldValuesForm.{{ $field->id }}"
                                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                @endswitch

                                @error("fieldValuesForm.{$field->id}") <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="$set('showFieldsForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
