@extends('layouts.admin')

@section('title', __('Generate link'))

@section('content')
    <div class="max-w-lg mx-auto" x-data="{ templateId: '{{ old('form_template_id', request('form_template_id', $templates->first()->id ?? '')) }}' }">
        <div class="mb-6 text-center">
            <div class="inline-flex items-center justify-center size-12 rounded-2xl bg-brand-blue-50 text-brand-blue mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Generate form link') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __("Fill in what you already know — the client won't be able to edit it.") }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            @if ($templates->isEmpty())
                <p class="text-sm text-gray-500 text-center">{{ __('There are no active on-demand form templates yet.') }}</p>
            @else
                <form method="POST" action="{{ route('admin.forms.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Form template') }}</label>
                        <select name="form_template_id" x-model="templateId"
                            class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @error('form_template_id') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    @foreach ($templates as $template)
                        <div x-show="templateId == {{ $template->id }}" class="space-y-5">
                            @php $knownFields = $template->fields->where('editable_by_recipient', false); @endphp
                            @foreach ($knownFields as $field)
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                        {{ $field->label }}
                                        @if ($field->is_required)
                                            <span class="text-brand-red">*</span>
                                        @endif
                                    </label>
                                    <input type="text" name="known_values[{{ $field->id }}]" value="{{ old("known_values.{$field->id}") }}"
                                        class="w-full h-11 border border-gray-300 rounded-lg px-3 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                                    @if ($field->help_text)
                                        <p class="text-xs text-gray-400 mt-1">{{ $field->help_text }}</p>
                                    @endif
                                    @error("known_values.{$field->id}") <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Signing window') }}</label>
                        <select name="expires_in" class="w-full h-11 border border-gray-300 rounded-lg px-3 bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            @foreach (['1' => __(':count day', ['count' => 1]), '3' => __(':count days', ['count' => 3]), '7' => __(':count days', ['count' => 7]), '15' => __(':count days', ['count' => 15]), '30' => __(':count days', ['count' => 30]), 'none' => __('No expiration')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('expires_in', '7') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">{{ __('After that time the link will stop accepting submissions.') }}</p>
                        @error('expires_in') <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-blue text-white py-2.5 rounded-lg font-semibold shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                        {{ __('Generate link') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
