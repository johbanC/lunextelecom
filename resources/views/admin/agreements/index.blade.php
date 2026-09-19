@extends('layouts.admin')

@section('title', __('COAM Equipment'))

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Total') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-800">{{ $counts['all'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">{{ __('Pending') }}</p>
            <p class="mt-1 text-3xl font-bold text-amber-700">{{ $counts['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-300 bg-gray-100 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Expired') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-700">{{ $counts['expired'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">{{ __('Signed') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700">{{ $counts['signed'] }}</p>
        </div>
        <div class="rounded-2xl border border-brand-red/30 bg-red-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-red">{{ __('To manage') }}</p>
            <p class="mt-1 text-3xl font-bold text-brand-red">{{ $counts['to_manage'] }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
        <div class="inline-flex flex-wrap p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach (['all' => __('All'), 'pending' => __('Pending'), 'expired' => __('Expired'), 'signed' => __('Signed'), 'to_manage' => __('To manage')] as $key => $label)
                <a href="{{ route('admin.agreements.index', $key === 'all' ? [] : ['status' => $key]) }}"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $status === $key ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if ($key === 'to_manage' && $counts['to_manage'] > 0)
                        <span class="ml-1 text-xs">({{ $counts['to_manage'] }})</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <div x-data="{ open: false, actionUrl: '', label: '' }" @keydown.escape.window="open = false">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[1000px]">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                        <th class="p-4 font-semibold">{{ __('Account ID') }}</th>
                        <th class="p-4 font-semibold">{{ __('Type') }}</th>
                        <th class="p-4 font-semibold">{{ __('Business / Client') }}</th>
                        <th class="p-4 font-semibold">{{ __('Status') }}</th>
                        <th class="p-4 font-semibold">{{ __('Managed') }}</th>
                        <th class="p-4 font-semibold">{{ __('Created') }}</th>
                        <th class="p-4 font-semibold">{{ __('Signed') }}</th>
                        <th class="p-4 font-semibold text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($agreements as $agreement)
                        <tr class="hover:bg-brand-blue-50/40 transition-colors">
                            <td class="p-4 font-bold text-gray-800">{{ $agreement->account_id }}</td>
                            <td class="p-4 text-gray-600">{{ \App\Models\Agreement::typeLabel($agreement->type) }}</td>
                            <td class="p-4 text-gray-600">{{ $agreement->business_name ?? $agreement->owner_name ?? '—' }}</td>
                            <td class="p-4">
                                @if ($agreement->isSigned())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> {{ __('Signed') }}
                                    </span>
                                @elseif ($agreement->isExpired())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-bold">
                                        <span class="size-1.5 rounded-full bg-gray-500"></span> {{ __('Expired') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span> {{ __('Pending') }}
                                    </span>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ $agreement->expires_at ? __('Expires :date', ['date' => $agreement->expires_at->format('m/d/Y')]) : __('No expiration') }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4">
                                @if (! $agreement->isSigned())
                                    <span class="text-gray-300">—</span>
                                @elseif ($agreement->isManaged())
                                    <span title="{{ __('Linked by :name on :date', ['name' => $agreement->manager?->name ?? '—', 'date' => $agreement->managed_at->format('m/d/Y H:i')]) }}"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-blue-50 text-brand-blue-700 text-xs font-bold whitespace-nowrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 shrink-0"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                        {{ $agreement->linked_ticket_number }}
                                    </span>
                                @else
                                    <button type="button"
                                        @click="open = true; actionUrl = '{{ route('admin.agreements.manage', $agreement) }}'; label = '{{ $agreement->account_id }}'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-red/10 text-brand-red text-xs font-bold hover:bg-brand-red/20 transition">
                                        {{ __('Manage') }}
                                    </button>
                                @endif
                            </td>
                            <td class="p-4 text-gray-500">
                                {{ $agreement->created_at->format('m/d/Y H:i') }}
                                <div class="text-xs text-gray-400">{{ __('by :name', ['name' => $agreement->creator?->name ?? '—']) }}</div>
                            </td>
                            <td class="p-4 text-gray-500">{{ $agreement->signed_at?->format('m/d/Y H:i') ?? '—' }}</td>
                            <td class="p-4 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('admin.agreements.show', $agreement) }}" class="text-brand-blue font-semibold hover:text-brand-blue-700 hover:underline">{{ __('View') }}</a>
                                @if ($agreement->isSigned())
                                    <a href="{{ route('admin.agreements.pdf', $agreement) }}" class="text-brand-blue font-semibold hover:text-brand-blue-700 hover:underline">PDF</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-10 mx-auto mb-2 text-gray-300">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                </svg>
                                {{ __('No forms in this view.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal: enlazar a un ticket --}}
        <div x-show="open" x-cloak x-transition.opacity @click.self="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6" @click.stop>
                <h2 class="text-lg font-bold text-gray-800 mb-1">{{ __('Link to ticket') }}</h2>
                <p class="text-sm text-gray-500 mb-4" x-text="'{{ __('Account') }}: ' + label"></p>
                <form method="POST" :action="actionUrl">
                    @csrf
                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Ticket number') }}</label>
                    <input type="text" name="ticket_number" required autofocus placeholder="{{ __('e.g. R-00004') }}"
                        class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    <p class="text-xs text-gray-400 mt-2">{{ __('This marks the signed document as managed — already sent to the responsible area.') }}</p>
                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-lg font-semibold text-sm text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="bg-brand-blue text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $agreements->links() }}
    </div>
@endsection
