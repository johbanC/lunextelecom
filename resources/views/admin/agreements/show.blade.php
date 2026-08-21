@extends('layouts.admin')

@section('title', __('Form') . ' ' . $agreement->account_id)

@section('content')
    <a href="{{ route('admin.agreements.index') }}" class="inline-flex items-center gap-1 text-brand-blue text-sm font-semibold hover:underline">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
        {{ __('Back to list') }}
    </a>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-start justify-between flex-wrap gap-4 p-6 border-b border-gray-100">
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ \App\Models\Agreement::typeLabel($agreement->type) }}</h1>
                <p class="text-sm text-gray-500 mt-1">Account ID: <span class="font-bold text-gray-700">{{ $agreement->account_id }}</span> &middot; {{ __('Date') }}: {{ $agreement->form_date->format('m/d/Y') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('Created by :name on :date', ['name' => $agreement->creator?->name ?? '—', 'date' => $agreement->created_at->format('m/d/Y H:i')]) }}</p>
            </div>
            @if ($agreement->isSigned())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span> {{ __('Signed :date', ['date' => $agreement->signed_at->format('m/d/Y H:i')]) }}
                </span>
            @elseif ($agreement->isExpired())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-200 text-gray-600 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-gray-500"></span> {{ __('Expired :date', ['date' => $agreement->expires_at->format('m/d/Y H:i')]) }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-sm font-bold">
                    <span class="size-1.5 rounded-full bg-amber-500"></span> {{ __('Pending signature') }}
                </span>
            @endif
        </div>

        <div class="p-6 space-y-6">
            @unless ($agreement->isSigned())
                @if ($agreement->isExpired())
                    <div class="flex items-start gap-3 rounded-xl border border-gray-300 bg-gray-100 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0 text-gray-500 mt-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="text-sm text-gray-600">{{ __('This link expired on :date and the client can no longer sign it. Extend the signing window below to reactivate it.', ['date' => $agreement->expires_at->format('m/d/Y H:i')]) }}</p>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-brand-blue-400/60 bg-brand-blue-50 p-5">
                        <p class="text-sm font-bold text-brand-blue-700 mb-2">{{ __('Link for the client') }}</p>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $agreement->publicUrl() }}" id="publicLink"
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
                            {{ __('Send this link to the client so they can fill out and sign the form.') }}
                            {{ $agreement->expires_at ? __('Expires on :date.', ['date' => $agreement->expires_at->format('m/d/Y H:i')]) : __('No expiration.') }}
                        </p>
                    </div>
                @endif

                <div class="rounded-xl border border-gray-200 p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">{{ $agreement->isExpired() ? __('Extend signing window') : __('Change signing window') }}</p>
                    <form method="POST" action="{{ route('admin.agreements.extend', $agreement) }}" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <select name="expires_in" class="h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none">
                            @foreach ($expirationOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-brand-blue text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-brand-blue-600 transition">
                            {{ __('Update signing window') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("Owner's Name") }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->owner_name }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Phone') }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->phone }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Business Name') }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->business_name }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Address') }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->address }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Last 4 Bank Acct') }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->last_4_bank }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Training Date') }}</span>
                        <span class="text-gray-800 font-medium">{{ optional($agreement->training_date)->format('m/d/Y') ?? '—' }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Total') }}</span>
                        <span class="text-brand-blue font-bold text-base">${{ number_format($agreement->total_amount, 2) }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Signature IP') }}</span>
                        <span class="text-gray-800 font-medium">{{ $agreement->signed_ip ?? '—' }}</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Signature') }}</p>
                    <div class="border border-gray-200 rounded-xl bg-gray-50 p-3 inline-block">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($agreement->signature_path) }}" alt="{{ __('Signature') }}" class="h-28">
                    </div>
                </div>

                <a href="{{ route('admin.agreements.pdf', $agreement) }}"
                    class="inline-flex items-center gap-2 bg-brand-blue text-white px-6 py-2.5 rounded-lg font-semibold shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        <path d="M3.5 15.75a.75.75 0 0 1 .75.75v1.5c0 .138.112.25.25.25h11a.25.25 0 0 0 .25-.25v-1.5a.75.75 0 0 1 1.5 0v1.5A1.75 1.75 0 0 1 15.5 19.5h-11A1.75 1.75 0 0 1 2.75 18v-1.5a.75.75 0 0 1 .75-.75Z" />
                    </svg>
                    {{ __('Download PDF') }}
                </a>
            @endunless
        </div>
    </div>
@endsection
