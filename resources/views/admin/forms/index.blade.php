@extends('layouts.admin')

@section('title', __('Forms'))

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
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
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">{{ __('Completed') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700">{{ $counts['completed'] }}</p>
        </div>
        <div class="rounded-2xl border border-brand-red/30 bg-red-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-red">{{ __('To manage') }}</p>
            <p class="mt-1 text-3xl font-bold text-brand-red">{{ $counts['to_manage'] }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
        <div class="inline-flex flex-wrap p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach (['all' => __('All'), 'pending' => __('Pending'), 'expired' => __('Expired'), 'completed' => __('Completed'), 'to_manage' => __('To manage')] as $key => $label)
                <a href="{{ route('admin.forms.index', $key === 'all' ? [] : ['status' => $key]) }}"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $status === $key ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if ($key === 'to_manage' && $counts['to_manage'] > 0)
                        <span class="ml-1 text-xs">({{ $counts['to_manage'] }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        @canany(['create', 'agreements.create'], \App\Models\FormSubmission::class)
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    {{ __('Generate link') }}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition
                    class="absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg py-1 text-sm z-10">
                    @can('agreements.create')
                        <a href="{{ route('admin.agreements.create') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">{{ __('COAM Equipment') }}</a>
                    @endcan
                    @can('create', \App\Models\FormSubmission::class)
                        @if ($onDemandTemplates->isEmpty())
                            <span class="block px-4 py-2 text-gray-400">{{ __('No form templates yet') }}</span>
                        @else
                            @foreach ($onDemandTemplates as $template)
                                <a href="{{ route('admin.forms.create', ['form_template_id' => $template->id]) }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">{{ $template->name }}</a>
                            @endforeach
                        @endif
                    @endcan
                    @can('viewAny', \App\Models\FormTemplate::class)
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('admin.form-templates.index') }}" class="block px-4 py-2 text-gray-500 hover:bg-gray-50 text-xs font-semibold uppercase tracking-wide">{{ __('Manage templates') }}</a>
                    @endcan
                </div>
            </div>
        @endcanany
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-400">
                    <th class="p-4 font-semibold">{{ __('Form') }}</th>
                    <th class="p-4 font-semibold">{{ __('Reference') }}</th>
                    <th class="p-4 font-semibold">{{ __('Status') }}</th>
                    <th class="p-4 font-semibold">{{ __('Managed') }}</th>
                    <th class="p-4 font-semibold">{{ __('Created') }}</th>
                    <th class="p-4 font-semibold">{{ __('Completed') }}</th>
                    <th class="p-4 font-semibold text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    <tr class="hover:bg-brand-blue-50/40 transition-colors">
                        <td class="p-4 font-bold text-gray-800">{{ $row['type'] }}</td>
                        <td class="p-4 text-gray-600">{{ $row['identifier'] }}</td>
                        <td class="p-4">
                            @if ($row['status'] === 'completed')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> {{ $row['completed_label'] }}
                                </span>
                            @elseif ($row['status'] === 'expired')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-bold">
                                    <span class="size-1.5 rounded-full bg-gray-500"></span> {{ __('Expired') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
                                    <span class="size-1.5 rounded-full bg-amber-500"></span> {{ __('Pending') }}
                                </span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if ($row['status'] !== 'completed')
                                <span class="text-gray-300">—</span>
                            @elseif ($row['managed'])
                                <span title="{{ __('Managed by :name on :date', ['name' => $row['manager_name'] ?? '—', 'date' => $row['managed_at']?->format('m/d/Y H:i')]) }}"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-blue-50 text-brand-blue-700 text-xs font-bold whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 shrink-0"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                    {{ $row['managed_label'] ?: __('Managed') }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">{{ __('Pending') }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-500">
                            {{ $row['created_at']->format('m/d/Y H:i') }}
                            <div class="text-xs text-gray-400">{{ __('by :name', ['name' => $row['creator_name'] ?? '—']) }}</div>
                        </td>
                        <td class="p-4 text-gray-500">{{ $row['completed_at']?->format('m/d/Y H:i') ?? '—' }}</td>
                        <td class="p-4 text-right whitespace-nowrap space-x-3">
                            <a href="{{ $row['show_url'] }}" class="text-brand-blue font-semibold hover:text-brand-blue-700 hover:underline">{{ __('View') }}</a>
                            @if ($row['pdf_url'])
                                <a href="{{ $row['pdf_url'] }}" class="text-brand-blue font-semibold hover:text-brand-blue-700 hover:underline">PDF</a>
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
        {{ $rows->links() }}
    </div>
@endsection
