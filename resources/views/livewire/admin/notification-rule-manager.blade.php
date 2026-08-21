<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Notification rules') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Who gets notified (email / in-platform) when a ticket event happens.') }}</p>
        </div>
        <button wire:click="newRule" class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            + {{ __('New rule') }}
        </button>
    </div>

    <x-how-it-works>
            <p class="text-sm text-gray-700 mb-4">
                {{ __('Each rule means "when this event happens, notify this group". A ticket also always emails whoever it is directly assigned to, even without a rule — rules are for everyone else who should know.') }}
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Setting up a rule') }}</h3>
                    <ol class="space-y-1.5 text-sm text-gray-600 list-decimal list-inside">
                        <li>{{ __('Event — what should trigger the notification.') }}</li>
                        <li>{{ __('Category (optional) — leave blank to apply to every category, or pick one to limit the rule to it.') }}</li>
                        <li>{{ __('Notify group — every member of that group gets notified.') }}</li>
                        <li>{{ __('Channel — email, in-platform (the bell), or both.') }}</li>
                    </ol>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('What each event means') }}</h3>
                    <dl class="space-y-1.5 text-sm">
                        <div><dt class="inline font-semibold text-gray-700">{{ __('Ticket created') }}:</dt> <dd class="inline text-gray-600">{{ __('a new ticket was opened.') }}</dd></div>
                        <div><dt class="inline font-semibold text-gray-700">{{ __('Status changed') }}:</dt> <dd class="inline text-gray-600">{{ __('someone changed a ticket\'s status (e.g. to Resolved).') }}</dd></div>
                        <div><dt class="inline font-semibold text-gray-700">{{ __('Reassigned (person or group)') }}:</dt> <dd class="inline text-gray-600">{{ __('the assignee or the related group changed.') }}</dd></div>
                        <div><dt class="inline font-semibold text-gray-700">{{ __('External comment added') }}:</dt> <dd class="inline text-gray-600">{{ __('someone left a comment marked "External".') }}</dd></div>
                        <div><dt class="inline font-semibold text-gray-700">{{ __('SLA about to breach') }} / {{ __('SLA breached') }}:</dt> <dd class="inline text-gray-600">{{ __('the ticket is close to, or past, its time limit.') }}</dd></div>
                        <div><dt class="inline font-semibold text-gray-700">{{ __('Form signed') }}:</dt> <dd class="inline text-gray-600">{{ __('a client signed a public form — this one has no category.') }}</dd></div>
                    </dl>
                </div>
            </div>
    </x-how-it-works>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[700px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Event') }}</th>
                    <th class="p-4 font-semibold">{{ __('Category') }}</th>
                    <th class="p-4 font-semibold">{{ __('Notifies group') }}</th>
                    <th class="p-4 font-semibold">{{ __('Channel') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
                    <th class="p-4 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rules as $rule)
                    <tr class="{{ ! $rule->is_active ? 'opacity-50' : '' }}">
                        <td class="p-4 font-medium text-gray-700">{{ match ($rule->event) {
                            'created' => __('Ticket created'),
                            'status_changed' => __('Status changed'),
                            'reassigned' => __('Reassigned (person or group)'),
                            'comment_added' => __('External comment added'),
                            'sla_warning' => __('SLA about to breach'),
                            'sla_breached' => __('SLA breached'),
                            'agreement_signed' => __('Form signed'),
                            default => $rule->event,
                        } }}</td>
                        <td class="p-4 text-gray-600">{{ $rule->category->name ?? __('All categories') }}</td>
                        <td class="p-4 text-gray-600">{{ $rule->group->name ?? '—' }}</td>
                        <td class="p-4 text-gray-600 capitalize">{{ $rule->channel }}</td>
                        <td class="p-4">
                            <button wire:click="toggleActive({{ $rule->id }})" @class([
                                'px-2.5 py-1 rounded-full text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $rule->is_active,
                                'bg-gray-100 text-gray-500' => ! $rule->is_active,
                            ])>
                                {{ $rule->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <button wire:click="editRule({{ $rule->id }})" class="text-brand-blue text-xs font-semibold hover:underline mr-3">{{ __('Edit') }}</button>
                            <button wire:click="deleteRule({{ $rule->id }})" wire:confirm="{{ __('Delete this rule?') }}" class="text-brand-red text-xs font-semibold hover:underline">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400">{{ __('No notification rules yet — nobody gets notified until you add one.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $form['id'] ? __('Edit rule') : __('New rule') }}</h2>
                <form wire:submit="saveRule" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Event') }}</label>
                        <select wire:model="form.event" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="created">{{ __('Ticket created') }}</option>
                            <option value="status_changed">{{ __('Status changed') }}</option>
                            <option value="reassigned">{{ __('Reassigned (person or group)') }}</option>
                            <option value="comment_added">{{ __('External comment added') }}</option>
                            <option value="sla_warning">{{ __('SLA about to breach') }}</option>
                            <option value="sla_breached">{{ __('SLA breached') }}</option>
                            <option value="agreement_signed">{{ __('Form signed') }}</option>
                        </select>
                    </div>
                    <div @class(['hidden' => ($form['event'] ?? null) === 'agreement_signed'])>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Category (optional)') }}</label>
                        <select wire:model="form.category_id" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @if (($form['event'] ?? null) === 'agreement_signed')
                            <p class="text-xs text-gray-400 mt-1">{{ __('Forms have no category — this rule applies to all signed forms.') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Notify group') }}</label>
                        <select wire:model="form.group_id" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('form.group_id') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Channel') }}</label>
                        <select wire:model="form.channel" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="both">{{ __('Email + in-platform') }}</option>
                            <option value="email">{{ __('Email only') }}</option>
                            <option value="platform">{{ __('In-platform only') }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                        {{ __('Active') }}
                    </label>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
