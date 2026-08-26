<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Reports') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Ticket volume, SLA compliance and workload for the selected period.') }}</p>
        </div>
        @if ($canExport)
            <button wire:click="exportCsv" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v6.638l1.96-2.158a.75.75 0 1 1 1.08 1.04l-3.25 3.5a.75.75 0 0 1-1.08 0l-3.25-3.5a.75.75 0 1 1 1.08-1.04l1.96 2.158V3.75A.75.75 0 0 1 10 3ZM3.5 14.75a.75.75 0 0 1 .75-.75h11.5a.75.75 0 0 1 0 1.5H4.25a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
                {{ __('Export CSV') }}
            </button>
        @endif
    </div>

    <x-how-it-works>
        <p class="text-sm text-gray-700 mb-4">
            {{ __('These numbers are scoped to your access level automatically — an Asesor sees their own tickets, a Líder de equipo sees their group\'s, and Director/Administración and Admin see everything.') }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('Filters and export') }}</h3>
                <ol class="space-y-1.5 text-sm text-gray-600 list-decimal list-inside">
                    <li>{{ __('Date range and ticket type — narrow the numbers to a period or to Retailer/Customer only.') }}</li>
                    <li>{{ __('Every chart and total below updates live as you change the filters.') }}</li>
                    <li>{{ __('Export CSV downloads the currently filtered list of tickets.') }}</li>
                </ol>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-brand-blue-700 mb-2">{{ __('SLA colors') }}</h3>
                <dl class="space-y-1.5 text-sm">
                    <div><dt class="inline font-semibold text-emerald-700">{{ __('On time') }}:</dt> <dd class="inline text-gray-600">{{ __('well within the category\'s time limit.') }}</dd></div>
                    <div><dt class="inline font-semibold text-amber-700">{{ __('Due soon') }}:</dt> <dd class="inline text-gray-600">{{ __('approaching the limit.') }}</dd></div>
                    <div><dt class="inline font-semibold text-brand-red">{{ __('Overdue (red SLA)') }}:</dt> <dd class="inline text-gray-600">{{ __('past the limit and needs attention.') }}</dd></div>
                    <div><dt class="inline font-semibold text-gray-700">{{ __('Done') }}:</dt> <dd class="inline text-gray-600">{{ __('resolved — no longer counts against SLA.') }}</dd></div>
                </dl>
            </div>
        </div>
    </x-how-it-works>

    <div class="flex flex-wrap items-end gap-3 mb-6">
        <div>
            <label class="block text-[11px] text-gray-400 mb-0.5">{{ __('From') }}</label>
            <input type="date" wire:model.live="dateFrom" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        </div>
        <div>
            <label class="block text-[11px] text-gray-400 mb-0.5">{{ __('To') }}</label>
            <input type="date" wire:model.live="dateTo" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
        </div>
        <select wire:model.live="ticketTypeFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All types') }}</option>
            <option value="retailer">{{ __('Retailer') }}</option>
            <option value="customer">{{ __('Customer') }}</option>
        </select>
        <select wire:model.live="categoryFilter" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">{{ __('Total tickets') }}</div>
            <div class="text-2xl font-bold text-gray-800 tabular-nums">{{ $total }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">{{ __('Overdue (red SLA)') }}</div>
            <div class="text-2xl font-bold text-brand-red tabular-nums">{{ $slaBreakdown['red'] ?? 0 }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">{{ __('Unassigned') }}</div>
            <div class="text-2xl font-bold text-amber-600 tabular-nums">{{ $unassignedCount }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">{{ __('Avg. resolution time') }}</div>
            <div class="text-2xl font-bold text-gray-800 tabular-nums">
                @if ($avgResolutionHours)
                    {{ $avgResolutionHours >= 24 ? number_format($avgResolutionHours / 24, 1) . ' ' . __('days') : number_format($avgResolutionHours, 1) . ' ' . __('hrs') }}
                @else
                    &mdash;
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Por estado --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('By status') }}</h2>
            @forelse (\App\Models\Ticket::STATUSES as $key => $label)
                @php($count = $byStatus[$key] ?? 0)
                <div class="flex items-center gap-3 mb-2.5 text-sm">
                    <span class="w-24 shrink-0 text-gray-600">{{ __($label) }}</span>
                    <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full bg-brand-blue rounded-full" style="width: {{ $total > 0 ? min(100, $count / $total * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-8 text-right font-semibold text-gray-700 tabular-nums">{{ $count }}</span>
                </div>
            @endforeach
        </div>

        {{-- SLA --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('SLA compliance') }}</h2>
            @foreach (['green' => ['On time', 'bg-emerald-500'], 'yellow' => ['Due soon', 'bg-amber-500'], 'red' => ['Overdue', 'bg-brand-red'], 'done' => ['Done', 'bg-gray-400']] as $key => [$label, $color])
                @php($count = $slaBreakdown[$key] ?? 0)
                <div class="flex items-center gap-3 mb-2.5 text-sm">
                    <span class="w-24 shrink-0 text-gray-600">{{ __($label) }}</span>
                    <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full {{ $color }} rounded-full" style="width: {{ $total > 0 ? min(100, $count / $total * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-8 text-right font-semibold text-gray-700 tabular-nums">{{ $count }}</span>
                </div>
            @endforeach
        </div>

        {{-- Por categoría --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Top categories') }}</h2>
            @forelse ($byCategory as $name => $count)
                <div class="flex items-center justify-between gap-3 mb-2 text-sm">
                    <span class="text-gray-600 truncate">{{ $name }}</span>
                    <span class="font-semibold text-gray-700 tabular-nums shrink-0">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">{{ __('No tickets in this period.') }}</p>
            @endforelse
        </div>

        {{-- Por asignado --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Workload by assignee') }}</h2>
            @forelse ($byAssignee as $name => $count)
                <div class="flex items-center justify-between gap-3 mb-2 text-sm">
                    <span class="text-gray-600 truncate">{{ $name }}</span>
                    <span class="font-semibold text-gray-700 tabular-nums shrink-0">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">{{ __('No assigned tickets in this period.') }}</p>
            @endforelse
        </div>
    </div>
</div>
