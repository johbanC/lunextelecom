<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('Admin Panel')) - Lunex Telecom</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-gray-800 bg-gray-50 bg-[radial-gradient(circle_at_top,_var(--color-brand-blue-50),_var(--color-gray-50)_55%)]">
    <nav class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.agreements.index') }}" class="flex items-center gap-3">
                    <img src="{{ asset('img/logo.png') }}" alt="Lunex Telecom" class="h-9 w-auto">
                </a>
                <div class="hidden sm:flex items-center gap-1">
                    <a href="{{ route('admin.agreements.index') }}"
                        class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                            {{ request()->routeIs('admin.agreements.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ __('Forms') }}
                    </a>
                    @can('viewAny', \App\Models\Ticket::class)
                        <a href="{{ route('admin.tickets.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.tickets.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('Tickets') }}
                        </a>
                    @endcan
                    @can('users.manage')
                        <a href="{{ route('admin.users.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.users.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('Users') }}
                        </a>
                    @endcan
                    @can('groups.manage')
                        <a href="{{ route('admin.groups.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.groups.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('Groups') }}
                        </a>
                    @endcan
                    @if (auth()->user()->can('reports.view.group') || auth()->user()->can('reports.view.all'))
                        <a href="{{ route('admin.reports.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.reports.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('Reports') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-locale-switcher />

                <livewire:admin.notification-bell />

                <a href="{{ route('admin.agreements.create') }}"
                    class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Generate link') }}</span>
                </a>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                        class="flex items-center justify-center size-9 rounded-full bg-brand-blue-100 text-brand-blue-700 font-bold text-sm hover:bg-brand-blue-100/70 transition">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </button>
                    <div x-show="open" x-cloak
                        class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white shadow-lg py-1 text-sm">
                        <p class="px-4 py-2 text-gray-400 text-xs truncate border-b border-gray-100">{{ auth()->user()->email }}</p>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-600 hover:bg-gray-50">{{ __('My profile') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-brand-red hover:bg-brand-red-50">{{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @if (request()->routeIs('admin.tickets.*'))
        <div class="bg-white border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center gap-1 py-2 text-sm">
                @can('viewAny', \App\Models\Ticket::class)
                    <a href="{{ route('admin.tickets.index') }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'text-brand-blue-700 bg-brand-blue-50' : 'text-gray-500 hover:text-gray-700' }}">{{ __('All tickets') }}</a>
                @endcan
                @can('create', \App\Models\Ticket::class)
                    <a href="{{ route('admin.tickets.create') }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ request()->routeIs('admin.tickets.create') ? 'text-brand-blue-700 bg-brand-blue-50' : 'text-gray-500 hover:text-gray-700' }}">{{ __('New ticket') }}</a>
                @endcan
                @can('catalog.manage')
                    <a href="{{ route('admin.tickets.catalog') }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ request()->routeIs('admin.tickets.catalog') ? 'text-brand-blue-700 bg-brand-blue-50' : 'text-gray-500 hover:text-gray-700' }}">{{ __('Catalog') }}</a>
                @endcan
                @can('notification_rules.manage')
                    <a href="{{ route('admin.tickets.notification-rules') }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ request()->routeIs('admin.tickets.notification-rules') ? 'text-brand-blue-700 bg-brand-blue-50' : 'text-gray-500 hover:text-gray-700' }}">{{ __('Notification rules') }}</a>
                @endcan
            </div>
        </div>
    @endif

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
        @if (session('status'))
            <div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-emerald-500">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                </svg>
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
