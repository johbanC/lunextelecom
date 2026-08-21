@props(['title' => null])

<div x-data="{ open: false }" class="rounded-2xl border border-brand-blue-100 bg-brand-blue-50 mb-6 overflow-hidden">
    <button type="button" x-on:click="open = ! open" class="w-full flex items-center justify-between gap-2 p-4 text-left">
        <span class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-brand-blue">
                <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Zm1-4a1.25 1.25 0 1 0 0 2.5A1.25 1.25 0 0 0 10 5Z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-bold text-brand-blue-700">{{ $title ?? __('How this works') }}</span>
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0 text-brand-blue transition-transform" x-bind:class="open && 'rotate-180'">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="px-5 pb-5">
        {{ $slot }}
    </div>
</div>
