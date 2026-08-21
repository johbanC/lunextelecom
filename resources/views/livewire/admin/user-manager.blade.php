<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Users') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Create accounts and assign their access level.') }}</p>
        </div>
        <button wire:click="newUser" class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            + {{ __('New user') }}
        </button>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[600px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Name') }}</th>
                    <th class="p-4 font-semibold">{{ __('Email') }}</th>
                    <th class="p-4 font-semibold">{{ __('Role') }}</th>
                    <th class="p-4 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="p-4 font-medium text-gray-700">{{ $user->name }}</td>
                        <td class="p-4 text-gray-600">{{ $user->email }}</td>
                        <td class="p-4">
                            @if ($user->roles->first())
                                <span class="px-2.5 py-1 rounded-full bg-brand-blue-50 text-brand-blue-700 text-xs font-bold">{{ $user->roles->first()->name }}</span>
                            @else
                                <span class="text-xs text-gray-400">{{ __('No role') }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <button wire:click="editUser({{ $user->id }})" class="text-brand-blue text-xs font-semibold hover:underline">{{ __('Edit') }}</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $form['id'] ? __('Edit user') : __('New user') }}</h2>
                <form wire:submit="saveUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="form.name" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('form.name') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Email') }}</label>
                        <input type="email" wire:model="form.email" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('form.email') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">
                            {{ $form['id'] ? __('New password (leave blank to keep current)') : __('Password') }}
                        </label>
                        <input type="password" wire:model="form.password" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('form.password') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Role') }}</label>
                        <select wire:model="form.role" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                        @error('form.role') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
