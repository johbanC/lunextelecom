<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Drafts') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Incomplete tickets — private until you finish and create them.') }}</p>
        </div>
        <a href="{{ route('admin.tickets.create') }}" wire:navigate
            class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
            {{ __('New ticket') }}
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[700px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Type') }}</th>
                    <th class="p-4 font-semibold">{{ __('Category / Issue') }}</th>
                    <th class="p-4 font-semibold">{{ __('Header') }}</th>
                    <th class="p-4 font-semibold">{{ __('Created by') }}</th>
                    <th class="p-4 font-semibold">{{ __('Last updated') }}</th>
                    <th class="p-4 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($drafts as $draft)
                    <tr class="hover:bg-brand-blue-50/40 transition-colors">
                        <td class="p-4 text-gray-600">{{ $draft->ticketType->name }}</td>
                        <td class="p-4 text-gray-600">
                            {{ $draft->category->name }}
                            <div class="text-xs text-gray-400">{{ $draft->issue->name }}</div>
                        </td>
                        <td class="p-4 text-gray-500">{{ $draft->headerTitle() ?? '—' }}</td>
                        <td class="p-4 text-gray-600">{{ $draft->creator->name }}</td>
                        <td class="p-4 text-gray-500">{{ $draft->updated_at->diffForHumans() }}</td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.tickets.drafts.edit', $draft) }}" wire:navigate
                                class="text-brand-blue font-semibold text-sm hover:underline">{{ __('Resume') }}</a>
                            <button type="button" wire:click="discard({{ $draft->id }})"
                                wire:confirm="{{ __('Discard this draft? This cannot be undone.') }}"
                                class="text-brand-red font-semibold text-sm hover:underline ml-4">{{ __('Discard') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400">{{ __('No drafts yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $drafts->links() }}</div>
</div>
