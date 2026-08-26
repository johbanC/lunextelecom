@extends('layouts.admin')

@section('title', __('Email detail'))

@section('content')
    <a href="{{ route('admin.email-log.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-blue-700 hover:underline mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
        {{ __('Back to Emails') }}
    </a>
    <h1 class="text-xl font-bold text-gray-800 mb-6">{{ __('Email detail') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-[360px_1fr] gap-6 items-start">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 space-y-4">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Status') }}</div>
                @if ($emailLog->status === 'sent')
                    <div class="font-semibold text-emerald-600">{{ __('Sent successfully') }}</div>
                @elseif ($emailLog->status === 'failed')
                    <div class="font-semibold text-brand-red">{{ __('Failed') }}</div>
                    @if ($emailLog->error_message)
                        <div class="text-xs text-gray-500 mt-1">{{ $emailLog->error_message }}</div>
                    @endif
                @else
                    <div class="font-semibold text-gray-500">{{ __('Pending') }}</div>
                @endif
            </div>

            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Purpose') }}</div>
                <div class="font-semibold text-gray-800">{{ __(\App\Notifications\TicketEventNotification::purposeLabel($emailLog->event)) }}</div>
            </div>

            @if ($emailLog->ticket)
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Related to') }}</div>
                    <a href="{{ route('admin.tickets.show', $emailLog->ticket) }}" class="font-semibold text-brand-blue-700 hover:underline">{{ $emailLog->ticket->ticket_number }}</a>
                </div>
            @elseif ($emailLog->agreement)
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Related to') }}</div>
                    <a href="{{ route('admin.agreements.show', $emailLog->agreement) }}" class="font-semibold text-brand-blue-700 hover:underline">{{ $emailLog->agreement->account_id }}</a>
                </div>
            @endif

            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                    {{ count($emailLog->all_recipients ?? []) > 1 ? __('Recipients (:n, same email)', ['n' => count($emailLog->all_recipients)]) : __('Recipient') }}
                </div>
                @if (count($emailLog->all_recipients ?? []) > 1)
                    <ul class="space-y-1">
                        @foreach ($emailLog->all_recipients as $recipient)
                            <li>
                                <div class="font-semibold text-gray-800">{{ $recipient['name'] ?? $recipient['email'] }}</div>
                                <div class="text-sm text-gray-500">{{ $recipient['email'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="font-semibold text-gray-800">{{ $emailLog->to_name ?? $emailLog->user?->name ?? '—' }}</div>
                    <div class="text-sm text-gray-500">{{ $emailLog->to_email }}</div>
                @endif
            </div>

            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Subject') }}</div>
                <div class="text-sm text-gray-800">{{ $emailLog->subject }}</div>
            </div>

            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Sent date') }}</div>
                <div class="text-sm text-gray-800">{{ ($emailLog->sent_at ?? $emailLog->created_at)->format('m/d/Y H:i:s') }}</div>
            </div>

            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ __('Opened') }}</div>
                @if ($emailLog->opened_at)
                    <div class="text-sm text-gray-800">{{ __('Yes') }} — {{ $emailLog->opened_at->format('m/d/Y H:i') }}</div>
                @else
                    <div class="text-sm text-gray-500">{{ __('Not opened') }}</div>
                @endif
            </div>

            @if ($emailLog->body_html)
                <div class="pt-4 border-t border-gray-100">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Resend') }}</div>

                    @if ($errors->any())
                        <div class="text-xs text-brand-red mb-2">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.email-log.resend', $emailLog) }}" class="space-y-2">
                        @csrf
                        <input type="email" name="to_email" placeholder="{{ __('Leave empty = same email') }}"
                            class="w-full h-10 border border-gray-300 rounded-lg px-3 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <button type="submit" class="w-full h-10 bg-brand-blue text-white rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                            {{ __('Resend email') }}
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2">{{ __('If you type a different email, it will be sent there instead (useful when the original was mistyped).') }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-gray-50 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-white text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                {{ __('Content preview') }}
            </div>
            @if ($emailLog->body_html)
                <iframe srcdoc="{{ $emailLog->body_html }}" class="w-full bg-white" style="height: 720px; border: 0;" sandbox=""></iframe>
            @else
                <div class="p-10 text-center text-gray-400 text-sm">{{ __('No content stored for this email.') }}</div>
            @endif
        </div>
    </div>
@endsection
