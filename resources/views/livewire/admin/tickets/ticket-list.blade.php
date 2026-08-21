<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <h1 class="text-xl font-bold text-gray-800">{{ __('Tickets') }}</h1>
        <div class="flex items-center gap-2">
            <button wire:click="exportCsv" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v6.638l1.96-2.158a.75.75 0 1 1 1.08 1.04l-3.25 3.5a.75.75 0 0 1-1.08 0l-3.25-3.5a.75.75 0 1 1 1.08-1.04l1.96 2.158V3.75A.75.75 0 0 1 10 3ZM3.5 14.75a.75.75 0 0 1 .75-.75h11.5a.75.75 0 0 1 0 1.5H4.25a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
                {{ __('Export CSV') }}
            </button>
            @can('create', \App\Models\Ticket::class)
                <a href="{{ route('admin.tickets.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    {{ __('New ticket') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-3 mb-4">
        <select wire:model.live="ticketTypeFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All types') }}</option>
            <option value="retailer">Retailer</option>
            <option value="customer">Customer</option>
        </select>
        <select wire:model.live="statusFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All statuses') }}</option>
            <option value="open">{{ __('Open') }}</option>
            <option value="in_progress">{{ __('In progress') }}</option>
            <option value="resolved">{{ __('Resolved') }}</option>
            <option value="closed">{{ __('Closed') }}</option>
        </select>
        <select wire:model.live="categoryFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="issueFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All issues') }}</option>
            @foreach ($issues as $issue)
                <option value="{{ $issue->id }}">{{ $issue->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="groupFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All groups') }}</option>
            @foreach ($groups as $group)
                <option value="{{ $group->id }}">{{ $group->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="assigneeFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All assignees') }}</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="slaFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All SLA') }}</option>
            <option value="red">{{ __('Overdue') }}</option>
            <option value="yellow">{{ __('Due soon') }}</option>
            <option value="green">{{ __('On time') }}</option>
            <option value="done">{{ __('Done') }}</option>
        </select>
        <div>
            <label class="block text-[11px] text-gray-400 mb-0.5">{{ __('From') }}</label>
            <input type="date" wire:model.live="dateFrom" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        </div>
        <div>
            <label class="block text-[11px] text-gray-400 mb-0.5">{{ __('To') }}</label>
            <input type="date" wire:model.live="dateTo" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        </div>
        <input type="text" wire:model.live.debounce.400ms="retailerFilter" placeholder="{{ __('Retailer code…') }}" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm w-40 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        <button wire:click="clearFilters" class="h-10 px-3 text-sm font-semibold text-gray-500 hover:text-gray-700 transition">{{ __('Clear filters') }}</button>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Ticket') }}</th>
                    <th class="p-4 font-semibold">{{ __('Type') }}</th>
                    <th class="p-4 font-semibold">{{ __('Category / Issue') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
                    <th class="p-4 font-semibold">{{ __('Priority') }}</th>
                    <th class="p-4 font-semibold">SLA</th>
                    <th class="p-4 font-semibold">{{ __('Assignee') }}</th>
                    <th class="p-4 font-semibold">{{ __('Created') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tickets as $ticket)
                    <tr class="hover:bg-brand-blue-50/40 transition-colors cursor-pointer"
                        onclick="window.location='{{ route('admin.tickets.show', $ticket) }}'">
                        <td class="p-4">
                            <div class="font-bold text-gray-800">{{ $ticket->ticket_number }}</div>
                            <div class="text-xs text-gray-400">{{ $ticket->headerTitle() ?? '—' }}</div>
                        </td>
                        <td class="p-4 text-gray-600">{{ $ticket->ticketType->name }}</td>
                        <td class="p-4 text-gray-600">
                            {{ $ticket->category->name }}
                            <div class="text-xs text-gray-400">{{ $ticket->issue->name }}</div>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold capitalize">
                                {{ str_replace('_', ' ', $ticket->status) }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span @class([
                                'text-xs font-bold capitalize',
                                'text-brand-red' => $ticket->priority === 'urgent',
                                'text-amber-600' => $ticket->priority === 'high',
                                'text-gray-500' => in_array($ticket->priority, ['normal', 'low'], true),
                            ])>{{ $ticket->priority }}</span>
                        </td>
                        <td class="p-4">
                            <x-sla-chip :status="$ticket->slaStatus()" />
                        </td>
                        <td class="p-4 text-gray-600">{{ $ticket->assignee?->name ?? '—' }}</td>
                        <td class="p-4 text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-gray-400">{{ __('No tickets yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</div>
