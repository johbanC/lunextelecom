<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">{{ __('Emails') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Every email the platform has sent — to whom, why, whether it went out, and whether it was opened.') }}</p>
    </div>

    <x-how-it-works>
        <p class="text-sm text-gray-700 mb-4">
            {{ __('Every notification sent by email (ticket created, status changed, reassigned, external comments, SLA alerts, signed forms) is logged here automatically — nothing to configure.') }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Status') }}</h3>
                <dl class="space-y-1.5 text-sm">
                    <div><dt class="inline font-semibold text-emerald-700">{{ __('Sent') }}:</dt> <dd class="inline text-gray-600">{{ __('the platform handed it off to the mail server successfully.') }}</dd></div>
                    <div><dt class="inline font-semibold text-brand-red">{{ __('Failed') }}:</dt> <dd class="inline text-gray-600">{{ __('the send attempt errored out — hover the row for details.') }}</dd></div>
                </dl>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Opened') }}</h3>
                <p class="text-sm text-gray-600">
                    {{ __('Detected with a tiny invisible tracking image inside the email — if the recipient\'s mail client loads images, we know. Some mail clients block images by default, so "not opened" doesn\'t always mean the email was ignored.') }}
                </p>
            </div>
        </div>
    </x-how-it-works>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Sent') }}</div>
            <div class="text-2xl font-bold text-emerald-600">{{ $totalSent }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Failed') }}</div>
            <div class="text-2xl font-bold text-brand-red">{{ $totalFailed }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Opened') }}</div>
            <div class="text-2xl font-bold text-brand-blue-700">{{ $totalOpened }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-3 mb-4">
        <select wire:model.live="statusFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All statuses') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
        </select>
        <select wire:model.live="openedFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('Opened or not') }}</option>
            <option value="yes">{{ __('Opened') }}</option>
            <option value="no">{{ __('Not opened') }}</option>
        </select>
        <select wire:model.live="eventFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All purposes') }}</option>
            @foreach ($events as $event)
                <option value="{{ $event }}">{{ __(\App\Notifications\TicketEventNotification::purposeLabel($event)) }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Recipient, subject, ticket…') }}" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm w-56 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        <button wire:click="clearFilters" class="h-10 px-3 text-sm font-semibold text-gray-500 hover:text-gray-700 transition">{{ __('Clear filters') }}</button>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Recipient') }}</th>
                    <th class="p-4 font-semibold">{{ __('Purpose') }}</th>
                    <th class="p-4 font-semibold">{{ __('Subject') }}</th>
                    <th class="p-4 font-semibold">{{ __('Related to') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
                    <th class="p-4 font-semibold">{{ __('Opened') }}</th>
                    <th class="p-4 font-semibold">{{ __('Sent') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-brand-blue-50/40 transition-colors cursor-pointer" title="{{ $log->status === 'failed' ? $log->error_message : '' }}"
                        onclick="window.location='{{ route('admin.email-log.show', $log) }}'">
                        <td class="p-4">
                            <div class="font-semibold text-gray-800">
                                {{ $log->to_name ?? $log->user?->name ?? '—' }}
                                @if (count($log->all_recipients ?? []) > 1)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold align-middle">{{ __('+:n more', ['n' => count($log->all_recipients) - 1]) }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400">{{ $log->to_email }}</div>
                        </td>
                        <td class="p-4 text-gray-600">{{ __(\App\Notifications\TicketEventNotification::purposeLabel($log->event)) }}</td>
                        <td class="p-4 text-gray-600 max-w-xs truncate" title="{{ $log->subject }}">{{ $log->subject }}</td>
                        <td class="p-4">
                            @if ($log->ticket)
                                <a href="{{ route('admin.tickets.show', $log->ticket) }}" onclick="event.stopPropagation()" class="text-brand-blue-700 font-semibold hover:underline">{{ $log->ticket->ticket_number }}</a>
                            @elseif ($log->agreement)
                                <a href="{{ route('admin.agreements.show', $log->agreement) }}" onclick="event.stopPropagation()" class="text-brand-blue-700 font-semibold hover:underline">{{ $log->agreement->account_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-4">
                            @if ($log->status === 'sent')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">{{ __('Sent') }}</span>
                            @elseif ($log->status === 'failed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 text-brand-red text-xs font-bold">{{ __('Failed') }}</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">{{ __('Pending') }}</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if ($log->opened_at)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-brand-blue-50 text-brand-blue-700 text-xs font-bold">
                                    {{ __('Opened') }}
                                </span>
                                <div class="text-xs text-gray-400 mt-1">{{ $log->opened_at->format('m/d/Y H:i') }}</div>
                            @else
                                <span class="text-xs text-gray-400">{{ __('Not opened') }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-500">{{ ($log->sent_at ?? $log->created_at)->format('m/d/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-gray-400">{{ __('No emails logged yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
