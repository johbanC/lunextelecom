<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Groups') }}</h1>
            <p class="text-sm text-gray-500">{{ __('"Related to" teams tickets can be routed to, and who belongs to each.') }}</p>
        </div>
        <button wire:click="newGroup" class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            + {{ __('New group') }}
        </button>
    </div>

    <x-how-it-works>
        <p class="text-sm text-gray-700 mb-4">
            {{ __('A group is a team tickets get routed to — "Related to" in the ticket form. Anyone in the group can pick up and resolve a ticket routed to it.') }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Setting up a group') }}</h3>
                <ol class="space-y-1.5 text-sm text-gray-600 list-decimal list-inside">
                    <li>{{ __('Applies to — which ticket forms (Retailer, Customer, or both) offer this group as an option.') }}</li>
                    <li>{{ __('Add members — anyone with an account can be added, and can belong to more than one group.') }}</li>
                    <li>{{ __('Set each member as Leader or Member — see the roles below.') }}</li>
                </ol>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Leader vs. Member') }}</h3>
                <dl class="space-y-1.5 text-sm">
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Leader') }}:</dt> <dd class="inline text-gray-600">{{ __('sees and can reassign every ticket routed to the group.') }}</dd></div>
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Member') }}:</dt> <dd class="inline text-gray-600">{{ __('only sees the tickets assigned to them.') }}</dd></div>
                </dl>
                <p class="text-xs text-gray-500 mt-3">{{ __('This connects to notification rules too: a rule that "notifies group" alerts every member of it.') }}</p>
            </div>
        </div>
    </x-how-it-works>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[600px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Name') }}</th>
                    <th class="p-4 font-semibold">{{ __('Applies to') }}</th>
                    <th class="p-4 font-semibold">{{ __('Members') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
                    <th class="p-4 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($groups as $group)
                    <tr class="{{ $group->is_active ? '' : 'opacity-50' }}">
                        <td class="p-4 font-medium text-gray-700">{{ $group->name }}</td>
                        <td class="p-4 text-gray-600">
                            {{ match ($group->applies_to) {
                                'retailer' => __('Retailer only'),
                                'customer' => __('Customer only'),
                                default => __('Retailer + Customer'),
                            } }}
                        </td>
                        <td class="p-4">
                            <button wire:click="manageMembers({{ $group->id }})" class="text-brand-blue text-xs font-semibold hover:underline">
                                {{ trans_choice('{0} No members|{1} :count member|[2,*] :count members', $group->members_count, ['count' => $group->members_count]) }}
                            </button>
                        </td>
                        <td class="p-4">
                            <button wire:click="toggleActive({{ $group->id }})" @class([
                                'px-2.5 py-1 rounded-full text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $group->is_active,
                                'bg-gray-100 text-gray-500' => ! $group->is_active,
                            ])>
                                {{ $group->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <button wire:click="manageMembers({{ $group->id }})" class="text-gray-500 text-xs font-semibold hover:underline mr-3">{{ __('Members') }}</button>
                            <button wire:click="editGroup({{ $group->id }})" class="text-brand-blue text-xs font-semibold hover:underline">{{ __('Edit') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-gray-400">{{ __('No groups yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal: Grupo --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $form['id'] ? __('Edit group') : __('New group') }}</h2>
                <form wire:submit="saveGroup" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="form.name" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('form.name') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Applies to (which ticket forms offer this group as "Related to")') }}</label>
                        <select wire:model="form.applies_to" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="both">{{ __('Retailer + Customer') }}</option>
                            <option value="retailer">{{ __('Retailer only') }}</option>
                            <option value="customer">{{ __('Customer only') }}</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: Miembros --}}
    @if ($membersGroup)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('membersGroupId', null)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-1">{{ __('Members of :name', ['name' => $membersGroup->name]) }}</h2>
                <p class="text-xs text-gray-500 mb-4">{{ __('Leaders can see and reassign all tickets routed to this group; members only see their own.') }}</p>

                <div class="space-y-1.5 mb-4 max-h-64 overflow-y-auto">
                    @forelse ($membersGroup->members as $member)
                        <div class="flex items-center justify-between gap-2 bg-gray-50 rounded-lg px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium text-gray-700">{{ $member->name }}</div>
                                <div class="text-xs text-gray-400">{{ $member->email }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <select wire:change="updateMemberRole({{ $member->id }}, $event.target.value)" class="h-8 border border-gray-300 rounded-lg px-2 bg-white text-xs focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                    <option value="member" @selected($member->pivot->role_in_group === 'member')>{{ __('Member') }}</option>
                                    <option value="leader" @selected($member->pivot->role_in_group === 'leader')>{{ __('Leader') }}</option>
                                </select>
                                <button wire:click="removeMember({{ $member->id }})" class="text-gray-400 hover:text-brand-red" title="{{ __('Remove') }}">&times;</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">{{ __('No members yet.') }}</p>
                    @endforelse
                </div>

                <div class="flex gap-2 border-t border-gray-100 pt-4">
                    <select wire:model="newMemberUserId" class="flex-1 h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="">{{ __('Add user…') }}</option>
                        @foreach ($allUsers as $user)
                            @unless ($membersGroup->members->contains($user->id))
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endunless
                        @endforeach
                    </select>
                    <select wire:model="newMemberRole" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="member">{{ __('Member') }}</option>
                        <option value="leader">{{ __('Leader') }}</option>
                    </select>
                    <button wire:click="addMember" class="px-4 h-10 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Add') }}</button>
                </div>

                <div class="flex justify-end pt-4">
                    <button wire:click="$set('membersGroupId', null)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
