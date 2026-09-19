<div x-data="{ show: false, message: '' }" x-on:notify.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3000)">
    <div x-show="show" x-cloak x-transition
        class="fixed top-4 right-4 z-[60] inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2.5 rounded-lg font-semibold text-sm shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
        </svg>
        <span x-text="message"></span>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Form templates') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Manage form templates and their fields.') }}</p>
        </div>
    </div>

    <x-how-it-works>
        <p class="text-sm text-gray-700 mb-4">
            {{ __('A form template controls what appears on a form. Each template has its own set of fields.') }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('The two levels') }}</h3>
                <ol class="space-y-1.5 text-sm text-gray-600 list-decimal list-inside">
                    <li>{{ __('Template — a form that can be sent on demand or published standalone (e.g. "Potential Retailer Sign Up").') }}</li>
                    <li>{{ __('Field — one question inside a template (label, type, required, help text, options).') }}</li>
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
                <p class="text-xs text-gray-500 mt-3">{{ __('A field that already has answers on real submissions can\'t be deleted — deactivate the template instead if it\'s no longer used.') }}</p>
            </div>
        </div>
    </x-how-it-works>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Templates --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Templates') }}</span>
                <button wire:click="newTemplate" class="text-xs font-semibold text-brand-blue hover:underline">+ {{ __('New') }}</button>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($templates as $template)
                    <li class="flex items-center justify-between px-4 py-3 text-sm gap-2 {{ $templateId === $template->id ? 'bg-brand-blue-50' : '' }}">
                        <button wire:click="selectTemplate({{ $template->id }})"
                            class="flex-1 text-left flex items-center gap-2 {{ $templateId === $template->id ? 'text-brand-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-800' }} {{ ! $template->is_active ? 'opacity-50' : '' }}">
                            {{ $template->name }}
                            <span class="text-xs text-gray-400">({{ $template->fields_count }})</span>
                            @unless ($template->is_active)
                                <span class="text-[10px] font-bold uppercase text-gray-400">{{ __('Inactive') }}</span>
                            @endunless
                        </button>
                        <button wire:click="editTemplate({{ $template->id }})" class="text-gray-400 hover:text-brand-blue" title="{{ __('Edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z" /></svg>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('No templates.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Campos --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Fields') }}</span>
                @if ($selectedTemplate)
                    <button wire:click="newField" class="text-xs font-semibold text-brand-blue hover:underline">+ {{ __('New') }}</button>
                @endif
            </div>
            @if ($selectedTemplate)
                <ul class="divide-y divide-gray-100">
                    @foreach ($selectedTemplate->fields as $field)
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
                            <div class="text-xs text-gray-400 mt-0.5 pl-[22px] flex items-center gap-1.5 flex-wrap">
                                <span>{{ $fieldTypes[$field->field_type] ?? $field->field_type }}@if ($field->pick_count) &middot; {{ __('Pick :n', ['n' => $field->pick_count]) }} @endif</span>
                                <span>&middot;</span>
                                <code class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-mono">{{ '{'.'{' }}{{ $field->key }}{{ '}'.'}' }}</code>
                                @unless ($field->editable_by_recipient)
                                    <span class="text-[10px] font-bold uppercase text-brand-blue-600">{{ __('Filled by agent') }}</span>
                                @endunless
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
                @if ($selectedTemplate->fields->isEmpty())
                    <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('This template has no fields yet.') }}</p>
                @endif
            @else
                <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('Select a template to see its fields.') }}</p>
            @endif
        </div>
    </div>

    {{-- Modal: Template --}}
    @if ($showTemplateForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4" x-data>
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showTemplateForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $templateForm['id'] ? __('Edit template') : __('New template') }}</h2>
                <form wire:submit="saveTemplate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="templateForm.name" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('templateForm.name') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Mode') }}</label>
                        <select wire:model="templateForm.mode" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="{{ \App\Models\FormTemplate::MODE_ON_DEMAND }}">{{ __('On demand') }}</option>
                            <option value="{{ \App\Models\FormTemplate::MODE_STANDALONE }}">{{ __('Standalone') }}</option>
                        </select>
                        @error('templateForm.mode') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="$wire.templateForm.mode === '{{ \App\Models\FormTemplate::MODE_STANDALONE }}'">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Slug') }}</label>
                        <input type="text" wire:model="templateForm.slug" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('templateForm.slug') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Layout') }}</label>
                        <select wire:model="templateForm.display_mode" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="{{ \App\Models\FormTemplate::DISPLAY_FIELDS }}">{{ __('Field list') }}</option>
                            <option value="{{ \App\Models\FormTemplate::DISPLAY_NARRATIVE }}">{{ __('Narrative text (read & sign)') }}</option>
                        </select>
                        @error('templateForm.display_mode') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Instructions') }}</label>
                        <textarea wire:model="templateForm.instructions" rows="8" class="w-full resize-none border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                        <p class="text-xs text-gray-400 mt-1" x-show="$wire.templateForm.display_mode === '{{ \App\Models\FormTemplate::DISPLAY_NARRATIVE }}'">
                            {{ __('In narrative layout, this is the whole message the client sees. Use') }}
                            <code>@{{field_key}}</code>
                            {{ __('to insert a field\'s value — e.g.') }}
                            <code>@{{full_name}}</code>.
                        </p>
                        @if ($templateForm['id'] && $selectedTemplate?->id === $templateForm['id'] && $selectedTemplate->fields->isNotEmpty())
                            <div class="mt-2 p-2.5 rounded-lg bg-gray-50 border border-gray-200">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">{{ __('Available variables') }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($selectedTemplate->fields as $field)
                                        <code class="px-1.5 py-0.5 rounded bg-white border border-gray-200 text-gray-600 font-mono text-[11px]" title="{{ $field->label }}">{{ '{'.'{' }}{{ $field->key }}{{ '}'.'}' }}</code>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @error('templateForm.instructions') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Notify group') }}</label>
                        <select wire:model="templateForm.notify_group_id" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" wire:model="templateForm.requires_signature" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                        {{ __('Requires signature') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" wire:model="templateForm.is_active" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                        {{ __('Active') }}
                    </label>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showTemplateForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
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
                    <div class="{{ $fieldForm['field_type'] === \App\Models\FormField::TYPE_PICK_N ? 'grid grid-cols-2 gap-3' : '' }}">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Type') }}</label>
                            <select wire:model.live="fieldForm.field_type" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                @foreach ($fieldTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($fieldForm['field_type'] === \App\Models\FormField::TYPE_PICK_N)
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
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" wire:model="fieldForm.editable_by_recipient" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                        {{ __('Editable by recipient') }}
                    </label>

                    @if (in_array($fieldForm['field_type'], [\App\Models\FormField::TYPE_SELECT, \App\Models\FormField::TYPE_CHECKBOX, \App\Models\FormField::TYPE_RADIO, \App\Models\FormField::TYPE_PICK_N], true))
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
