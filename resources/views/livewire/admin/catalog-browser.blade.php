<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Ticket catalog') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Manage categories, issues and their fields.') }}</p>
        </div>
        <div class="inline-flex p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach ($ticketTypes as $type)
                <button wire:click="selectType('{{ $type->code }}')"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $ticketTypeCode === $type->code ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $type->name }}
                </button>
            @endforeach
        </div>
    </div>

    <x-how-it-works>
        <p class="text-sm text-gray-700 mb-4">
            {{ __('The catalog controls what agents see when they open a ticket. It has three levels, and the field set on a ticket always depends on the Issue — never the Category.') }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('The three levels') }}</h3>
                <ol class="space-y-1.5 text-sm text-gray-600 list-decimal list-inside">
                    <li>{{ __('Category — the top-level grouping (e.g. "R Account inquiry"). Also sets the default group and SLA days.') }}</li>
                    <li>{{ __('Issue — the specific reason for the ticket, inside a category (e.g. "Password Reset"). This is where the field set is actually defined.') }}</li>
                    <li>{{ __('Field — one question the agent fills out inside an issue (label, type, required, help text, options).') }}</li>
                </ol>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Field types') }}</h3>
                <dl class="space-y-1.5 text-sm">
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Text') }} / {{ __('Textarea') }}:</dt> <dd class="inline text-gray-600">{{ __('free typing, one line or several.') }}</dd></div>
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Select') }} / {{ __('Radio (single)') }}:</dt> <dd class="inline text-gray-600">{{ __('pick exactly one option.') }}</dd></div>
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Checkbox (multiple)') }}:</dt> <dd class="inline text-gray-600">{{ __('pick any number of options.') }}</dd></div>
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Pick N') }}:</dt> <dd class="inline text-gray-600">{{ __('like checkboxes, but must pick exactly the configured count.') }}</dd></div>
                </dl>
                <p class="text-xs text-gray-500 mt-3">{{ __('A field that already has answers on real tickets can\'t be deleted — deactivate the issue instead if it\'s no longer used.') }}</p>
            </div>
        </div>
    </x-how-it-works>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Categorías --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Categories') }}</span>
                <button wire:click="newCategory" class="text-xs font-semibold text-brand-blue hover:underline">+ {{ __('New') }}</button>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <li class="flex items-center justify-between px-4 py-3 text-sm gap-2 {{ $categoryId === $category->id ? 'bg-brand-blue-50' : '' }}">
                        <div class="flex flex-col shrink-0">
                            <button wire:click="moveCategory({{ $category->id }}, 'up')" @if ($loop->first) disabled @endif
                                class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move up') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 4.5a.75.75 0 0 1 .53.22l5 5a.75.75 0 1 1-1.06 1.06L10.75 7.06V15a.75.75 0 0 1-1.5 0V7.06L5.53 10.78a.75.75 0 1 1-1.06-1.06l5-5A.75.75 0 0 1 10 4.5Z" clip-rule="evenodd" /></svg>
                            </button>
                            <button wire:click="moveCategory({{ $category->id }}, 'down')" @if ($loop->last) disabled @endif
                                class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move down') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 15.5a.75.75 0 0 1-.53-.22l-5-5a.75.75 0 1 1 1.06-1.06l3.72 3.72V5a.75.75 0 0 1 1.5 0v7.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-5 5a.75.75 0 0 1-.53.22Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <button wire:click="selectCategory({{ $category->id }})"
                            class="flex-1 text-left flex items-center gap-2 {{ $categoryId === $category->id ? 'text-brand-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-800' }} {{ ! $category->is_active ? 'opacity-50' : '' }}">
                            {{ $category->name }}
                            <span class="text-xs text-gray-400">({{ $category->issues_count }})</span>
                            @unless ($category->is_active)
                                <span class="text-[10px] font-bold uppercase text-gray-400">{{ __('Inactive') }}</span>
                            @endunless
                        </button>
                        <button wire:click="editCategory({{ $category->id }})" class="text-gray-400 hover:text-brand-blue" title="{{ __('Edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z" /></svg>
                        </button>
                        <button wire:click="toggleCategoryActive({{ $category->id }})" class="text-gray-400 hover:text-amber-600" title="{{ $category->is_active ? __('Deactivate') : __('Activate') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4c0 .2.08.39.22.53l2.5 2.5a.75.75 0 1 0 1.06-1.06l-2.28-2.28v-3.7Z" clip-rule="evenodd" /></svg>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('No categories.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Issues --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Issues') }}</span>
                @if ($categoryId)
                    <button wire:click="newIssue" class="text-xs font-semibold text-brand-blue hover:underline">+ {{ __('New') }}</button>
                @endif
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($issues as $issue)
                    <li class="flex items-center justify-between px-4 py-3 text-sm gap-2 {{ $issueId === $issue->id ? 'bg-brand-blue-50' : '' }}">
                        <div class="flex flex-col shrink-0">
                            <button wire:click="moveIssue({{ $issue->id }}, 'up')" @if ($loop->first) disabled @endif
                                class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move up') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 4.5a.75.75 0 0 1 .53.22l5 5a.75.75 0 1 1-1.06 1.06L10.75 7.06V15a.75.75 0 0 1-1.5 0V7.06L5.53 10.78a.75.75 0 1 1-1.06-1.06l5-5A.75.75 0 0 1 10 4.5Z" clip-rule="evenodd" /></svg>
                            </button>
                            <button wire:click="moveIssue({{ $issue->id }}, 'down')" @if ($loop->last) disabled @endif
                                class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move down') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 15.5a.75.75 0 0 1-.53-.22l-5-5a.75.75 0 1 1 1.06-1.06l3.72 3.72V5a.75.75 0 0 1 1.5 0v7.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-5 5a.75.75 0 0 1-.53.22Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <button wire:click="selectIssue({{ $issue->id }})"
                            class="flex-1 text-left {{ $issueId === $issue->id ? 'text-brand-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-800' }} {{ ! $issue->is_active ? 'opacity-50' : '' }}">
                            {{ $issue->name }}
                            @unless ($issue->is_active)
                                <span class="text-[10px] font-bold uppercase text-gray-400 ml-1">{{ __('Inactive') }}</span>
                            @endunless
                        </button>
                        <button wire:click="editIssue({{ $issue->id }})" class="text-gray-400 hover:text-brand-blue" title="{{ __('Edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z" /></svg>
                        </button>
                        <button wire:click="toggleIssueActive({{ $issue->id }})" class="text-gray-400 hover:text-amber-600" title="{{ $issue->is_active ? __('Deactivate') : __('Activate') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4c0 .2.08.39.22.53l2.5 2.5a.75.75 0 1 0 1.06-1.06l-2.28-2.28v-3.7Z" clip-rule="evenodd" /></svg>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('Select a category.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Campos --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Fields') }}</span>
                @if ($selectedIssue)
                    <button wire:click="newField" class="text-xs font-semibold text-brand-blue hover:underline">+ {{ __('New') }}</button>
                @endif
            </div>
            @if ($selectedIssue)
                <ul class="divide-y divide-gray-100">
                    @foreach ($selectedIssue->fieldDefinitions as $field)
                        <li class="px-4 py-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="flex flex-col shrink-0">
                                        <button wire:click="moveField({{ $field->id }}, 'up')" @if ($loop->first) disabled @endif
                                            class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move up') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 4.5a.75.75 0 0 1 .53.22l5 5a.75.75 0 1 1-1.06 1.06L10.75 7.06V15a.75.75 0 0 1-1.5 0V7.06L5.53 10.78a.75.75 0 1 1-1.06-1.06l5-5A.75.75 0 0 1 10 4.5Z" clip-rule="evenodd" /></svg>
                                        </button>
                                        <button wire:click="moveField({{ $field->id }}, 'down')" @if ($loop->last) disabled @endif
                                            class="text-gray-300 hover:text-brand-blue disabled:opacity-30 disabled:hover:text-gray-300" title="{{ __('Move down') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M10 15.5a.75.75 0 0 1-.53-.22l-5-5a.75.75 0 1 1 1.06-1.06l3.72 3.72V5a.75.75 0 0 1 1.5 0v7.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-5 5a.75.75 0 0 1-.53.22Z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>
                                    <button wire:click="editField({{ $field->id }})" class="text-left font-medium text-gray-700 hover:text-brand-blue truncate">{{ $field->label }}</button>
                                </div>
                                @if ($field->is_required)
                                    <span class="text-[10px] font-bold uppercase text-brand-red shrink-0">{{ __('Required') }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5 pl-[22px]">
                                {{ $fieldTypes[$field->field_type] ?? $field->field_type }}@if ($field->pick_count) &middot; {{ __('Pick :n', ['n' => $field->pick_count]) }} @endif
                            </div>
                            @if ($field->options->isNotEmpty())
                                <div class="mt-1.5 pl-[22px] flex flex-wrap gap-1">
                                    @foreach ($field->options as $option)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[11px]">{{ $option->value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($selectedIssue->fieldDefinitions->isEmpty())
                    <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('This issue has no extra fields.') }}</p>
                @endif
            @else
                <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('Select an issue to see its fields.') }}</p>
            @endif
        </div>
    </div>

    {{-- Modal: Categoría --}}
    @if ($showCategoryForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showCategoryForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $categoryForm['id'] ? __('Edit category') : __('New category') }}</h2>
                <form wire:submit="saveCategory" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="categoryForm.name" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('categoryForm.name') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Default "Related to" group') }}</label>
                        <select wire:model="categoryForm.default_related_to_group_id" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('SLA yellow (days)') }}</label>
                            <input type="number" min="1" wire:model="categoryForm.sla_yellow_days" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @error('categoryForm.sla_yellow_days') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('SLA red (days)') }}</label>
                            <input type="number" min="1" wire:model="categoryForm.sla_red_days" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @error('categoryForm.sla_red_days') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showCategoryForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: Issue --}}
    @if ($showIssueForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showIssueForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $issueForm['id'] ? __('Edit issue') : __('New issue') }}</h2>
                <form wire:submit="saveIssue" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="issueForm.name" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('issueForm.name') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showIssueForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: Campo --}}
    @if ($showFieldForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showFieldForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $fieldForm['id'] ? __('Edit field') : __('New field') }}</h2>
                <form wire:submit="saveField" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Label') }}</label>
                        <input type="text" wire:model="fieldForm.label" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('fieldForm.label') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="{{ $fieldForm['field_type'] === \App\Models\FieldDefinition::TYPE_PICK_N ? 'grid grid-cols-2 gap-3' : '' }}">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Type') }}</label>
                            <select wire:model.live="fieldForm.field_type" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                @foreach ($fieldTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($fieldForm['field_type'] === \App\Models\FieldDefinition::TYPE_PICK_N)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Pick count') }}</label>
                                <input type="number" min="1" max="10" wire:model="fieldForm.pick_count" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                @error('fieldForm.pick_count') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Help text (tooltip)') }}</label>
                        <textarea wire:model="fieldForm.help_text" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" wire:model="fieldForm.is_required" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                        {{ __('Required') }}
                    </label>

                    @if (in_array($fieldForm['field_type'], [\App\Models\FieldDefinition::TYPE_SELECT, \App\Models\FieldDefinition::TYPE_CHECKBOX, \App\Models\FieldDefinition::TYPE_RADIO, \App\Models\FieldDefinition::TYPE_PICK_N], true))
                        <div class="border-t border-gray-100 pt-3">
                            <label class="block text-xs font-semibold text-gray-500 mb-2">{{ __('Options') }}</label>
                            @if ($fieldForm['id'])
                                <div class="space-y-1.5 mb-2">
                                    @forelse ($fieldForm['options'] as $optionId => $optionValue)
                                        <div class="flex items-center justify-between gap-2 bg-gray-50 rounded-lg px-3 py-1.5 text-sm">
                                            <span class="text-gray-700">{{ $optionValue }}</span>
                                            <button type="button" wire:click="removeFieldOption({{ $optionId }})" class="text-gray-400 hover:text-brand-red">&times;</button>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400">{{ __('No options yet.') }}</p>
                                    @endforelse
                                </div>
                                <div class="flex gap-2">
                                    <input type="text" wire:model="newOptionValue" wire:keydown.enter.prevent="addFieldOption" placeholder="{{ __('New option…') }}" class="flex-1 h-9 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                    <button type="button" wire:click="addFieldOption" class="px-3 h-9 rounded-lg text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">{{ __('Add') }}</button>
                                </div>
                            @else
                                <p class="text-xs text-gray-400">{{ __('Save the field first to add options.') }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2">
                        @if ($fieldForm['id'])
                            <button type="button" wire:click="deleteField({{ $fieldForm['id'] }})" wire:confirm="{{ __('Delete this field?') }}" class="px-3 py-2 rounded-lg text-sm font-semibold text-brand-red hover:bg-brand-red-50 transition">{{ __('Delete') }}</button>
                        @else
                            <span></span>
                        @endif
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="$set('showFieldForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Close') }}</button>
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
