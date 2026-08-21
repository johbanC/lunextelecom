@extends('layouts.admin')

@section('title', __('Forms'))

@section('content')
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
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
    </div>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
        <div class="inline-flex flex-wrap p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach (['all' => __('All'), 'pending' => __('Pending'), 'expired' => __('Expired'), 'signed' => __('Signed')] as $key => $label)
                <a href="{{ route('admin.agreements.index', $key === 'all' ? [] : ['status' => $key]) }}"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $status === $key ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">Account ID</th>
                    <th class="p-4 font-semibold">{{ __('Type') }}</th>
                    <th class="p-4 font-semibold">{{ __('Business / Client') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
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
                        <td colspan="7" class="p-10 text-center text-gray-400">
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

    <div class="mt-4">
        {{ $agreements->links() }}
    </div>
@endsection
