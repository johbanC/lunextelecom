<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\Agreement::typeLabel($agreement->type) }}</title>
    @vite(['resources/css/app.css', 'resources/js/agreement-form.js'])
</head>
<body class="min-h-screen bg-gray-100 bg-[radial-gradient(circle_at_top,_var(--color-brand-blue-50),_var(--color-gray-100)_60%)] p-4 md:p-10 font-sans text-gray-800">

    <div class="max-w-3xl mx-auto">
        <div class="flex justify-end mb-3">
            <x-locale-switcher class="bg-white" />
        </div>

        <div class="rounded-2xl bg-white shadow-xl shadow-gray-300/40 overflow-hidden">
        <div class="flex justify-center py-5 border-b border-gray-100">
            <img src="{{ asset('img/logo.png') }}" alt="Lunex Telecom" class="h-12 w-auto">
        </div>
        <div class="bg-gradient-to-r from-brand-blue to-brand-blue-700 text-white text-center py-4 px-4">
            <h1 class="font-bold text-lg tracking-wide uppercase">{{ \App\Models\Agreement::typeLabel($agreement->type) }}</h1>
        </div>

        @if ($agreement->isSigned())
            <div class="p-10 text-center space-y-3">
                <div class="inline-flex items-center justify-center size-14 rounded-full bg-emerald-100 text-emerald-600 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ __('This form has already been signed.') }}</p>
                <p class="text-sm text-gray-500">{{ __('Signed on :date. If you think this is a mistake, contact Lunex Telecom.', ['date' => $agreement->signed_at->format('d/m/Y H:i')]) }}</p>
            </div>
        @elseif ($expired)
            <div class="p-10 text-center space-y-3">
                <div class="inline-flex items-center justify-center size-14 rounded-full bg-gray-100 text-gray-500 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-800">{{ __('This signing link has expired.') }}</p>
                <p class="text-sm text-gray-500">{{ __('Contact Lunex Telecom to have a new link sent to you.') }}</p>
            </div>
        @else
            <form id="agreementForm" method="POST" action="{{ route('public.agreements.store', $agreement->uuid) }}" class="p-5 md:p-8 space-y-6"
                data-signature-required="{{ __('Please add your signature before submitting.') }}">
                @csrf
                <input type="hidden" name="signature" id="signatureInput">

                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-2 rounded-full bg-brand-blue-50 border border-brand-blue-100 pl-3 pr-4 py-1.5">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-blue-700">Account ID</span>
                        <span class="text-sm font-bold text-brand-blue">{{ $agreement->account_id }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-full bg-gray-100 border border-gray-200 pl-3 pr-4 py-1.5">
                        <span class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ __('Date') }}</span>
                        <span class="text-sm font-bold text-gray-700">{{ $agreement->form_date->format('m/d/Y') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __("Owner's Name") }}</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 uppercase font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __('Primary Phone Number') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __('Business Name') }}</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 uppercase font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __('Location Address') }}</label>
                        <input type="text" name="address" value="{{ old('address') }}" required
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 uppercase font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __('Last 4 Digits of Bank Acct') }}</label>
                        <input type="text" name="last_4_bank" id="last4BankInput" value="{{ old('last_4_bank') }}" required maxlength="4" pattern="\d{4}"
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ __('Training Date') }}</label>
                        <input type="date" name="training_date" value="{{ old('training_date') }}"
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-brand-red-50 bg-brand-red-50/60 border-l-4 border-l-brand-red p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0 text-brand-red mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <p class="text-sm font-semibold italic text-brand-red-600 flex items-center flex-wrap gap-1">
                        <span>{{ __('I authorize Lunex Telecom, Inc. to initiate debit ACH to the account ending with') }}</span>
                        <input type="text" name="authorization_digits" id="authorizationDigitsInput" value="{{ old('authorization_digits', old('last_4_bank')) }}" maxlength="4" required readonly
                            class="w-14 text-center bg-gray-100 border border-brand-red-50 rounded px-1 py-0.5 text-brand-red font-bold outline-none cursor-not-allowed" placeholder="____">
                        <span>(xxxx).</span>
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[480px]">
                        <thead>
                            <tr class="bg-brand-blue text-white text-sm">
                                <th class="p-3 font-semibold">{{ __('Item') }}</th>
                                <th class="p-3 font-semibold text-center w-24">{{ __('Rate') }}</th>
                                <th class="p-3 font-semibold text-center w-24">{{ __('Quantity') }}</th>
                                <th class="p-3 font-semibold text-center w-32">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($catalog as $item)
                                <tr class="item-row" data-rate="{{ $item['rate'] }}">
                                    <td class="p-3">
                                        <div class="font-bold text-brand-blue">{{ $item['name'] }}</div>
                                        @if ($item['description'])
                                            <div class="text-xs italic text-gray-500">{{ $item['description'] }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-600">
                                        {{ $item['rate'] > 0 ? '$' . number_format($item['rate'], 2) : __('Free') }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="items[{{ $item['key'] }}]" min="0" value="0"
                                            class="qty-input w-16 mx-auto block border border-gray-300 rounded-lg text-center h-9 focus:ring-2 focus:ring-brand-blue outline-none">
                                    </td>
                                    <td class="p-3 text-center font-bold row-total text-gray-700">$0.00</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50">
                                <td colspan="3" class="p-3 text-right font-bold text-brand-blue">{{ __('TOTAL AMOUNT') }}</td>
                                <td class="p-3 text-center font-bold text-lg text-brand-blue" id="grandTotal">$0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl bg-brand-red-50 border border-brand-red-50 text-brand-red-600 text-sm p-4">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
                            {{ __('Submit Authorization') }}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        @endif
        </div>
    </div>
</body>
</html>
