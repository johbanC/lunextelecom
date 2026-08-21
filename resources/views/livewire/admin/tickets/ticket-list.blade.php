<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <h1 class="text-xl font-bold text-gray-800">{{ __('Tickets') }}</h1>
        <a href="{{ route('admin.tickets.create') }}" wire:navigate
            class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
            {{ __('New ticket') }}
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-4">
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
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Ticket') }}</th>
                    <th class="p-4 font-semibold">{{ __('Type') }}</th>
                    <th class="p-4 font-semibold">{{ __('Category / Issue') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
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
                            @php($sla = $ticket->slaStatus())
                            <span @class([
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $sla === 'green',
                                'bg-amber-100 text-amber-700' => $sla === 'yellow',
                                'bg-red-100 text-red-700' => $sla === 'red',
                            ])>
                                <span @class([
                                    'size-1.5 rounded-full',
                                    'bg-emerald-500' => $sla === 'green',
                                    'bg-amber-500' => $sla === 'yellow',
                                    'bg-red-500' => $sla === 'red',
                                ])></span>
                                {{ ucfirst($sla) }}
                            </span>
                        </td>
                        <td class="p-4 text-gray-600">{{ $ticket->assignee?->name ?? '—' }}</td>
                        <td class="p-4 text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-gray-400">{{ __('No tickets yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</div>
