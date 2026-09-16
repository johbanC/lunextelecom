@extends('layouts.admin')

@section('title', $submission->template->name)

@section('content')
    <a href="{{ route('admin.forms.index') }}" class="inline-flex items-center gap-1 text-brand-blue text-sm font-semibold hover:underline">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
        {{ __('Back to list') }}
    </a>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-start justify-between flex-wrap gap-4 p-6 border-b border-gray-100">
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ $submission->template->name }}</h1>
                <p class="text-xs text-gray-400 mt-1">{{ __('Created by :name on :date', ['name' => $submission->creator?->name ?? '—', 'date' => $submission->created_at->format('m/d/Y H:i')]) }}</p>
            </div>
            @if ($submission->isSubmitted())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span> {{ __('Submitted :date', ['date' => $submission->submitted_at->format('m/d/Y H:i')]) }}
                </span>
            @elseif ($submission->isExpired())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-200 text-gray-600 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-gray-500"></span> {{ __('Expired :date', ['date' => $submission->expires_at->format('m/d/Y H:i')]) }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-amber-500"></span> {{ __('Pending submission') }}
                </span>
            @endif
        </div>

        <div class="p-6 space-y-6">
            @unless ($submission->isSubmitted())
                @if ($submission->isExpired())
                    <div class="flex items-start gap-3 rounded-xl border border-gray-300 bg-gray-100 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0 text-gray-500 mt-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="text-sm text-gray-600">{{ __('This link expired on :date and the client can no longer submit it.', ['date' => $submission->expires_at->format('m/d/Y H:i')]) }}</p>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-brand-blue-400/60 bg-brand-blue-50 p-5">
                        <p class="text-sm font-bold text-brand-blue-700 mb-2">{{ __('Link for the client') }}</p>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $submission->publicUrl() }}" id="publicLink"
                                class="flex-1 h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm text-gray-600">
                            <button type="button"
                                x-data="{ copied: false }"
                                @click="navigator.clipboard.writeText(document.getElementById('publicLink').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center gap-2 bg-brand-blue text-white px-4 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path d="M7.5 3.375c0-1.036.84-1.875 1.875-1.875h.375a3.75 3.75 0 0 1 3.75 3.75v1.875C13.5 8.161 14.34 9 15.375 9h1.875A3.75 3.75 0 0 1 21 12.75v3.375C21 17.16 20.16 18 19.125 18h-9.75A1.875 1.875 0 0 1 7.5 16.125V3.375Z" />
                                    <path d="M15 5.25a5.23 5.23 0 0 0-1.279-3.434 9.768 9.768 0 0 1 6.963 6.963A5.23 5.23 0 0 0 17.25 7.5h-1.875A.375.375 0 0 1 15 7.125V5.25ZM4.875 6H6v10.125A3.375 3.375 0 0 0 9.375 19.5H16.5v1.125c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V7.875C3 6.839 3.84 6 4.875 6Z" />
                                </svg>
                                <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"></span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('Send this link to the client so they can complete the form.') }}
                            {{ $submission->expires_at ? __('Expires on :date.', ['date' => $submission->expires_at->format('m/d/Y H:i')]) : __('No expiration.') }}
                        </p>
                    </div>
                @endif
            @endunless

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Data') }}</p>
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($submission->template->fields as $field)
                                @php $value = $submission->values->firstWhere('form_field_id', $field->id); @endphp
                                <tr>
                                    <td class="p-3 w-1/3 font-semibold text-gray-600 bg-gray-50 align-top">{{ $field->label }}</td>
                                    <td class="p-3 text-gray-800">{{ $value?->value ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="p-4 text-center text-gray-400">{{ __('This template has no fields.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($submission->isSubmitted())
                @if ($submission->signature_path)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Signature') }}</p>
                        <div class="border border-gray-200 rounded-xl bg-gray-50 p-3 inline-block">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($submission->signature_path) }}" alt="{{ __('Signature') }}" class="h-28">
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ __('Signature IP') }}: {{ $submission->signed_ip ?? '—' }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if ($submission->isManaged())
                        <span title="{{ __('Managed by :name on :date', ['name' => $submission->manager?->name ?? '—', 'date' => $submission->managed_at->format('m/d/Y H:i')]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-brand-blue-50 text-brand-blue-700 text-sm font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            {{ __('Managed :date', ['date' => $submission->managed_at->format('m/d/Y H:i')]) }}
                            @if ($submission->reference_note)
                                — {{ $submission->reference_note }}
                            @endif
                        </span>
                    @else
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = true"
                                class="inline-flex items-center gap-2 bg-brand-red/10 text-brand-red px-4 py-2.5 rounded-lg font-semibold text-sm hover:bg-brand-red/20 transition">
                                {{ __('Manage') }}
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity @click.self="open = false"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6" @click.stop>
                                    <h2 class="text-lg font-bold text-gray-800 mb-1">{{ __('Mark as managed') }}</h2>
                                    <p class="text-sm text-gray-500 mb-4">{{ $submission->template->name }}</p>
                                    <form method="POST" action="{{ route('admin.forms.manage', $submission) }}">
                                        @csrf
                                        <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Reference note') }}</label>
                                        <input type="text" name="reference_note" autofocus placeholder="{{ __('e.g. ticket / account reference') }}"
                                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                        <p class="text-xs text-gray-400 mt-2">{{ __('This marks the submission as managed — already sent to the responsible area.') }}</p>
                                        <div class="flex justify-end gap-2 mt-5">
                                            <button type="button" @click="open = false" class="px-4 py-2 rounded-lg font-semibold text-sm text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                                            <button type="submit" class="bg-brand-blue text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
