@props(['text'])

@if (filled($text))
    <span x-data="{ open: false }" class="relative inline-block align-middle">
        <button type="button" x-on:click="open = ! open" x-on:click.outside="open = false"
            class="inline-flex items-center justify-center size-4 rounded-full bg-gray-200 text-gray-500 text-[10px] font-bold leading-none hover:bg-brand-blue-100 hover:text-brand-blue transition"
            aria-label="{{ __('Help') }}">
            i
        </button>
        <div x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute z-20 left-0 top-full mt-1.5 w-60 rounded-lg bg-gray-800 text-white text-xs leading-relaxed p-2.5 shadow-lg">
            {{ $text }}
        </div>
    </span>
@endif
