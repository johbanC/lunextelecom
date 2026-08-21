<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false" type="button"
        class="relative flex items-center justify-center size-9 rounded-full text-gray-500 hover:bg-gray-100 transition">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
            <path fill-rule="evenodd" d="M10 2a6 6 0 0 0-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 0 0 .515 1.076 32.94 32.94 0 0 0 3.256.508 3.5 3.5 0 0 0 6.972 0 32.94 32.94 0 0 0 3.256-.508.75.75 0 0 0 .515-1.076A11.448 11.448 0 0 1 16 8a6 6 0 0 0-6-6ZM8.05 14.943a33.54 33.54 0 0 0 3.9 0 2 2 0 0 1-3.9 0Z" clip-rule="evenodd" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-brand-red text-white text-[10px] font-bold">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>
    <div x-show="open" x-cloak
        class="absolute right-0 mt-2 w-80 rounded-xl border border-gray-200 bg-white shadow-lg py-1 text-sm max-h-96 overflow-y-auto">
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
            <span class="font-semibold text-gray-700">{{ __('Notifications') }}</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs font-semibold text-brand-blue hover:underline">{{ __('Mark all read') }}</button>
            @endif
        </div>
        @forelse ($notifications as $notification)
            <a href="{{ $notification->data['url'] ?? '#' }}" wire:click="markAsRead('{{ $notification->id }}')"
                class="block px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-brand-blue-50/40' }}">
                <div class="flex items-start gap-2">
                    @unless ($notification->read_at)
                        <span class="size-1.5 rounded-full bg-brand-blue mt-1.5 shrink-0"></span>
                    @endunless
                    <div class="min-w-0">
                        <div class="font-medium text-gray-700 truncate">{{ $notification->data['subject'] ?? __('Notification') }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $notification->data['line'] ?? '' }}</div>
                        <div class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            </a>
        @empty
            <p class="px-4 py-6 text-center text-gray-400 text-sm">{{ __('No notifications yet.') }}</p>
        @endforelse
    </div>
</div>
