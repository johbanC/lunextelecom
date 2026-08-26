<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">{{ __('Demo data') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Populate the platform with generic sample tickets to show how it looks and works — for demos only, never available in production.') }}</p>
    </div>

    <div class="flex items-start gap-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0">
            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>{{ __('Generated tickets are tagged internally so they can be cleared in one click. This page only exists outside production.') }}</div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 max-w-xl">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Generate') }}</h2>
        <p class="text-sm text-gray-500 mb-4">
            {{ __('Creates real-looking tickets spread across every status, priority, and SLA color (green/yellow/red/done), with random assignees and a few left as drafts — so a client can see the full picture in one shot.') }}
        </p>

        <div class="flex items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('How many?') }}</label>
                <input type="number" wire:model="count" min="1" max="200"
                    class="w-28 h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                @error('count') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                class="inline-flex items-center gap-2 bg-brand-blue text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                <span wire:loading.remove wire:target="generate">{{ __('Generate demo tickets') }}</span>
                <span wire:loading wire:target="generate">{{ __('Generating…') }}</span>
            </button>
        </div>

        <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                {{ __(':count demo ticket(s) currently in the system.', ['count' => $demoCount]) }}
            </div>
            @if ($demoCount > 0)
                <button wire:click="clear" wire:confirm="{{ __('Delete all demo tickets? This cannot be undone.') }}"
                    class="text-brand-red text-sm font-semibold hover:underline">
                    {{ __('Clear all demo tickets') }}
                </button>
            @endif
        </div>
    </div>
</div>
