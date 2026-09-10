<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Roles') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Create roles and turn permissions on or off for each one. Assign a role to a user on the Users screen.') }}</p>
        </div>
        <button wire:click="newRole" class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            + {{ __('New role') }}
        </button>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[600px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Role') }}</th>
                    <th class="p-4 font-semibold">{{ __('Users') }}</th>
                    <th class="p-4 font-semibold">{{ __('Permissions') }}</th>
                    <th class="p-4 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($roles as $role)
                    <tr class="{{ $selectedRoleId === $role->id ? 'bg-brand-blue-50' : '' }}">
                        <td class="p-4 font-medium text-gray-700">
                            {{ $role->name }}
                            @if ($role->name === 'Admin')
                                <span class="ml-1 text-[10px] font-bold uppercase text-gray-400">{{ __('Locked') }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-600">{{ $role->users_count }}</td>
                        <td class="p-4 text-gray-600">{{ $role->name === 'Admin' ? __('All') : $role->permissions_count }}</td>
                        <td class="p-4 text-right">
                            <button wire:click="selectRole({{ $role->id }})" class="text-brand-blue text-xs font-semibold hover:underline">{{ __('Edit') }}</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-gray-800">{{ $creatingNew ? __('New role') : __('Edit role') }}</h2>
                <button wire:click="closeForm" class="text-gray-400 hover:text-gray-600 text-sm font-semibold">{{ __('Close') }}</button>
            </div>

            @if ($locked)
                <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
                    {{ __('The Admin role always has every permission and cannot be edited or deleted.') }}
                </div>
            @endif

            <div class="max-w-sm mb-6">
                <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Role name') }}</label>
                <input type="text" wire:model="roleName" @disabled($locked)
                    class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition disabled:bg-gray-50 disabled:text-gray-400">
                @error('roleName') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                @error('selectedPermissions.*') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-5">
                @foreach ($permissionGroups as $groupName => $permissions)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ $groupName }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-start gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm cursor-pointer transition has-[:checked]:border-brand-blue has-[:checked]:bg-brand-blue-50 {{ $locked ? 'opacity-60 cursor-not-allowed' : 'hover:bg-gray-50' }}">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}" @disabled($locked)
                                        class="mt-0.5 rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                                    <span class="text-gray-700">{{ $labels[$permission] ?? $permission }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-2 pt-6 mt-6 border-t border-gray-100">
                @if (! $creatingNew && ! $locked && $selectedRoleId)
                    <button wire:click="deleteRole({{ $selectedRoleId }})" wire:confirm="{{ __('Delete this role?') }}"
                        class="px-3 py-2 rounded-lg text-sm font-semibold text-brand-red hover:bg-brand-red-50 transition">{{ __('Delete role') }}</button>
                @else
                    <span></span>
                @endif
                <div class="flex items-center gap-2">
                    <button wire:click="closeForm" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                    <button wire:click="save" @disabled($locked)
                        class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition disabled:opacity-40">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
