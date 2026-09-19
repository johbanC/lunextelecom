<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $template->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/form-submission.js'])
</head>
<body class="min-h-screen bg-gray-100 bg-[radial-gradient(circle_at_top,_var(--color-brand-blue-50),_var(--color-gray-100)_60%)] p-4 md:p-10 font-sans text-gray-800">

    <div class="max-w-3xl mx-auto">
        <div class="flex justify-end mb-3">
            <x-locale-switcher class="bg-white" :locales="['en' => 'EN', 'es' => 'ES', 'hi' => 'हि']" />
        </div>

        <div class="rounded-2xl bg-white shadow-xl shadow-gray-300/40 overflow-hidden">
        <div class="flex justify-center py-5 border-b border-gray-100">
            <img src="{{ asset('img/logo.png') }}" alt="Lunex Telecom" class="h-12 w-auto">
        </div>
        <div class="bg-gradient-to-r from-brand-blue to-brand-blue-700 text-white text-center py-4 px-4">
            <h1 class="font-bold text-lg tracking-wide uppercase">{{ $template->name }}</h1>
        </div>

        @if ($submission->isSubmitted())
            <div class="p-10 text-center space-y-3">
                <div class="inline-flex items-center justify-center size-14 rounded-full bg-emerald-100 text-emerald-600 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ __('This form has already been submitted.') }}</p>
                <p class="text-sm text-gray-500">{{ __('Submitted on :date. If you think this is a mistake, contact Lunex Telecom.', ['date' => $submission->submitted_at->format('m/d/Y H:i')]) }}</p>
            </div>
        @elseif ($expired)
            <div class="p-10 text-center space-y-3">
                <div class="inline-flex items-center justify-center size-14 rounded-full bg-gray-100 text-gray-500 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ __('This link has expired.') }}</p>
                <p class="text-sm text-gray-500">{{ __('Contact Lunex Telecom to have a new link sent to you.') }}</p>
            </div>
        @else
            <form id="formSubmission" method="POST" action="{{ route('public.forms.store', $submission->uuid) }}" class="p-5 md:p-8 space-y-6"
                data-signature-required="{{ __('Please add your signature before submitting.') }}">
                @csrf
                @if ($template->requires_signature)
                    <input type="hidden" name="signature" id="signatureInput">
                @endif

                @if ($template->isNarrative())
                    <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line leading-relaxed">
                        {{ $submission->interpolatedInstructions() }}
                    </div>
                @elseif ($template->instructions)
                    <p class="text-sm text-gray-600">{{ $template->instructions }}</p>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl bg-brand-red-50 border border-brand-red-50 text-brand-red-600 text-sm p-4">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @unless ($template->isNarrative())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                        @foreach ($template->fields as $field)
                            <x-form-field-input :field="$field" :value="$knownValues[$field->id] ?? null" :readonly="!$field->editable_by_recipient" />
                        @endforeach
                    </div>
                @endunless

                @if ($template->requires_signature)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">{{ __('Signature') }}</h3>
                        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white">
                            <canvas id="signatureCanvas" class="w-full h-32 cursor-crosshair rounded-lg"></canvas>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <button type="button" id="clearSignature" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-red hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Clear Signature') }}
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-brand-blue text-white px-6 py-2.5 rounded-lg font-bold shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                                {{ __('Submit') }}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-brand-blue text-white px-6 py-2.5 rounded-lg font-bold shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                            {{ __('Submit') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endif
            </form>
        @endif
        </div>
    </div>
</body>
</html>
