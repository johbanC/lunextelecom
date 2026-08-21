<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('New ticket') }}</h1>
            <p class="text-sm text-gray-500">{{ __('The field set below depends on the selected Issue.') }}</p>
        </div>
        <div class="inline-flex p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach ($ticketTypes as $type)
                <button type="button" wire:click="selectType('{{ $type->code }}')"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $ticketTypeCode === $type->code ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $type->name }}
                </button>
            @endforeach
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Encabezado fijo --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Header') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach (array_keys($header) as $key)
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                        <input type="text" wire:model="header.{{ $key }}"
                            @if ($key === 'retailer_code') list="retailer-codes-list" placeholder="{{ __('Lookup code from the other platform') }}" @endif
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error("header.{$key}") <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                @if ($ticketTypeCode === 'retailer')
                    <datalist id="retailer-codes-list">
                        @foreach ($retailerCodes as $code)
                            <option value="{{ $code }}"></option>
                        @endforeach
                    </datalist>
                @endif
            </div>

            @if ($ticketTypeCode === 'customer')
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-600">{{ __('Extra customers') }}</h3>
                        <button type="button" wire:click="addExtraCustomer" class="text-xs font-semibold text-brand-blue hover:underline">
                            + {{ __('Add extra customer') }}
                        </button>
                    </div>
                    @foreach ($extraCustomers as $index => $extra)
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" placeholder="{{ __('Full name') }}" wire:model="extraCustomers.{{ $index }}.full_name"
                                class="flex-1 h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <input type="text" placeholder="{{ __('Phone') }}" wire:model="extraCustomers.{{ $index }}.phone"
                                class="flex-1 h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <button type="button" wire:click="removeExtraCustomer({{ $index }})" class="text-brand-red text-xs font-semibold">
                                {{ __('Remove') }}
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Clasificación --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Classification') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Category') }} *</label>
                    <select wire:model.live="categoryId" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Select…') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Issue') }} *</label>
                    <select wire:model.live="issueId" @disabled(! $categoryId) class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition disabled:bg-gray-100">
                        <option value="">{{ __('Select…') }}</option>
                        @foreach ($issues as $issue)
                            <option value="{{ $issue->id }}">{{ $issue->name }}</option>
                        @endforeach
                    </select>
                    @error('issueId') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Priority') }} *</label>
                    <select wire:model="priority" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @foreach (['low' => __('Low'), 'normal' => __('Normal'), 'high' => __('High'), 'urgent' => __('Urgent')] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Status') }} *</label>
                    <select wire:model="status" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'resolved' => __('Resolved'), 'closed' => __('Closed')] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Related to') }}</label>
                    <select wire:model="relatedToGroupId" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Unassigned group') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Assignee') }}</label>
                    <select wire:model="assigneeId" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Campos dinámicos del Issue --}}
        @if ($selectedIssue)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ $selectedIssue->name }}</h2>

                @if ($selectedIssue->fieldDefinitions->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('This issue has no extra fields.') }}</p>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($selectedIssue->fieldDefinitions as $field)
                        <div class="{{ in_array($field->field_type, ['textarea', 'checkbox', 'pick_n']) ? 'sm:col-span-2' : '' }}">
                            <label class="block text-sm font-medium text-gray-600 mb-1">
                                {{ $field->label }}
                                @if ($field->is_required) <span class="text-brand-red">*</span> @endif
                                @if ($field->field_type === 'pick_n') <span class="text-xs text-gray-400">({{ __('Pick :n', ['n' => $field->pick_count]) }})</span> @endif
                            </label>

                            @switch($field->field_type)
                                @case('textarea')
                                    <textarea wire:model="fieldValues.{{ $field->id }}" rows="3"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                                    @break

                                @case('date')
                                    <input type="date" wire:model="fieldValues.{{ $field->id }}"
                                        class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                    @break

                                @case('select')
                                    <select wire:model="fieldValues.{{ $field->id }}"
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
                                                <input type="radio" wire:model="fieldValues.{{ $field->id }}" value="{{ $option->value }}"
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
                                                <input type="checkbox" wire:model.live="fieldValues.{{ $field->id }}" value="{{ $option->value }}"
                                                    class="rounded text-brand-blue focus:ring-brand-blue">
                                                {{ $option->value }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @default
                                    <input type="text" wire:model="fieldValues.{{ $field->id }}"
                                        class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @endswitch

                            @error("fieldValues.{$field->id}") <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Adjuntos --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Attachments') }}</h2>
            <input type="file" wire:model="newAttachments" multiple
                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-600 file:text-xs file:font-semibold hover:file:bg-gray-200">
            @error('newAttachments') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
            @error('newAttachments.*') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
            <div wire:loading wire:target="newAttachments" class="text-xs text-gray-400 mt-1">{{ __('Uploading…') }}</div>
            @if ($newAttachments)
                <ul class="mt-2 space-y-1">
                    @foreach ($newAttachments as $file)
                        <li class="text-xs text-gray-500">{{ $file->getClientOriginalName() }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-brand-blue text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition"
                wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('Create ticket') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    </form>
</div>
