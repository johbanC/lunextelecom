<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Thank you') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100 bg-[radial-gradient(circle_at_top,_var(--color-brand-blue-50),_var(--color-gray-100)_60%)] flex items-center justify-center p-4 font-sans">
    <div class="max-w-md w-full space-y-3">
        <div class="flex justify-end">
            <x-locale-switcher :locales="['en' => 'EN', 'es' => 'ES', 'hi' => 'हि']" />
        </div>
        <div class="w-full bg-white shadow-xl shadow-gray-300/40 rounded-2xl p-8 text-center space-y-4">
            <img src="{{ asset('img/logo.png') }}" alt="Lunex Telecom" class="h-12 w-auto mx-auto">
            <div class="inline-flex items-center justify-center size-14 rounded-full bg-emerald-100 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Form submitted!') }}</h1>
            <p class="text-gray-500 text-sm">{{ __('Thank you, :name. Your authorization for account :account was received and signed successfully.', ['name' => $agreement->owner_name, 'account' => $agreement->account_id]) }}</p>
        </div>
    </div>
</body>
</html>
