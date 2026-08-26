<div>
    @if ($editingDraft)
        <div class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            <div>{{ __('You are editing a draft. Only you (and Admin/Director) can see it — nobody is notified until you create the ticket.') }}</div>
        </div>
    @endif

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $editingDraft ? __('Edit draft') : __('New ticket') }}</h1>
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
                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __(ucwords(str_replace('_', ' ', $key))) }}</label>
                        @if ($key === 'retailer_code')
                            <div class="relative" x-data="{ copied: false, hasValue: false }" x-init="hasValue = $refs.retailerCodeInput.value.length > 0">
                                <input type="text" wire:model="header.{{ $key }}" x-ref="retailerCodeInput"
                                    list="retailer-codes-list" placeholder="{{ __('Lookup code from the other platform') }}"
                                    @input="$el.value = $el.value.toUpperCase(); hasValue = $el.value.length > 0"
                                    class="w-full h-11 border border-gray-300 rounded-lg pl-3 pr-10 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition uppercase placeholder:normal-case">
                                <button type="button" x-show="hasValue" x-cloak
                                    @click="navigator.clipboard.writeText($refs.retailerCodeInput.value).then(() => { copied = true; setTimeout(() => copied = false, 1500); })"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-brand-blue transition"
                                    title="{{ __('Copy Retailer Code') }}">
                                    <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h3.879a1.5 1.5 0 0 1 1.06.44l3.122 3.12A1.5 1.5 0 0 1 17 6.622V12.5a1.5 1.5 0 0 1-1.5 1.5h-1v-3.379a3 3 0 0 0-.879-2.121L10.5 5.379A3 3 0 0 0 8.379 4.5H7v-1Z" />
                                        <path d="M4.5 6A1.5 1.5 0 0 0 3 7.5v9A1.5 1.5 0 0 0 4.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-6.879a1.5 1.5 0 0 0-.44-1.06L9.44 5.439A1.5 1.5 0 0 0 8.378 5H4.5Z" />
                                    </svg>
                                    <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 text-emerald-500">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <input type="text" wire:model="header.{{ $key }}"
                                class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @endif
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
                        @foreach (\App\Models\Ticket::PRIORITIES as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Status') }} *</label>
                    <select wire:model="status" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @foreach (\App\Models\Ticket::STATUSES as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
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
                        <div class="{{ in_array($field->field_type, ['textarea', 'checkbox', 'pick_n']) || $field->key === 'method_of_verification_details' ? 'sm:col-span-2' : '' }}">
                            <label class="block text-sm font-medium text-gray-600 mb-1">
                                {{ $field->label }}
                                @if ($field->is_required) <span class="text-brand-red">*</span> @endif
                                @if ($field->field_type === 'pick_n') <span class="text-xs text-gray-400">({{ __('Pick at least :n', ['n' => $field->pick_count]) }})</span> @endif
                                <x-field-help :text="$field->help_text" />
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
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach ($field->options as $option)
                                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 cursor-pointer select-none transition hover:border-gray-300 has-[:checked]:border-brand-blue has-[:checked]:bg-brand-blue-50 has-[:checked]:text-brand-blue-700 has-[:checked]:font-semibold">
                                                <input type="radio" wire:model="fieldValues.{{ $field->id }}" value="{{ $option->value }}"
                                                    class="text-brand-blue focus:ring-brand-blue focus:ring-offset-0">
                                                {{ $option->value }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('checkbox')
                                @case('pick_n')
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        @foreach ($field->options as $option)
                                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 cursor-pointer select-none transition hover:border-gray-300 has-[:checked]:border-brand-blue has-[:checked]:bg-brand-blue-50 has-[:checked]:text-brand-blue-700 has-[:checked]:font-semibold">
                                                <input type="checkbox" wire:model.live="fieldValues.{{ $field->id }}" value="{{ $option->value }}"
                                                    class="rounded text-brand-blue focus:ring-brand-blue focus:ring-offset-0">
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
            <div class="flex items-center gap-3">
                <label for="newAttachmentsInput"
                    class="inline-flex items-center gap-2 bg-gray-100 text-gray-600 text-xs font-semibold px-3 py-1.5 rounded-md cursor-pointer hover:bg-gray-200 transition shrink-0">
                    {{ __('Choose files') }}
                </label>
                <span class="text-xs text-gray-400">{{ $newAttachments ? __(':count file(s) selected', ['count' => count($newAttachments)]) : __('No files chosen') }}</span>
            </div>
            <input type="file" id="newAttachmentsInput" wire:model="newAttachments" multiple class="sr-only">
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

        <div class="flex justify-end items-center gap-3">
            <button type="button" wire:click="saveDraft"
                class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-50 transition"
                wire:loading.attr="disabled" wire:target="saveDraft">
                <span wire:loading.remove wire:target="saveDraft">{{ __('Save as draft') }}</span>
                <span wire:loading wire:target="saveDraft">{{ __('Saving…') }}</span>
            </button>
            <button type="submit"
                class="inline-flex items-center gap-2 bg-brand-blue text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition"
                wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('Create ticket') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    </form>
</div>
