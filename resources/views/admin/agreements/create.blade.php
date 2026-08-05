@extends('layouts.admin')

@section('title', __('Generate link'))

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="mb-6 text-center">
            <div class="inline-flex items-center justify-center size-12 rounded-2xl bg-brand-blue-50 text-brand-blue mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Generate form link') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __("This information will be locked and the client won't be able to edit it.") }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <form method="POST" action="{{ route('admin.agreements.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Form type') }}</label>
                    <select name="type" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        <option value="coam_equipment">GA COAM Equipment Inquiry</option>
                    </select>
                    @error('type') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Account ID</label>
                    <input type="text" name="account_id" value="{{ old('account_id') }}" required
                        class="w-full h-11 border border-gray-300 rounded-lg px-3 uppercase focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"
                        oninput="this.value = this.value.toUpperCase()">
                    @error('account_id') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Date') }}</label>
                    <input type="date" name="form_date" value="{{ old('form_date', now()->toDateString()) }}" required
                        class="w-full h-11 border border-gray-300 rounded-lg px-3 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    @error('form_date') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Signing window') }}</label>
                    <select name="expires_in" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @foreach ($expirationOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('expires_in', '15') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">{{ __('After that time the link will stop accepting signatures (you can extend it later if needed).') }}</p>
                    @error('expires_in') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full bg-brand-blue text-white py-2.5 rounded-lg font-semibold shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                    {{ __('Generate link') }}
                </button>
            </form>
        </div>
    </div>
@endsection
