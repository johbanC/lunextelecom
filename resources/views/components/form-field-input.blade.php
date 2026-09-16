@props(['field', 'value' => null, 'readonly' => false])

@php
    $inputClasses = 'w-full h-11 border border-gray-300 rounded-lg px-3 font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition'
        . ($readonly ? ' bg-gray-100 text-gray-500 cursor-not-allowed' : '');
    $name = $field->isMultiValue() ? "values[{$field->key}][]" : "values[{$field->key}]";
    $selected = old("values.{$field->key}", $value);
@endphp

<div>
    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">
        {{ $field->label }}@if ($field->is_required)<span class="text-brand-red">*</span>@endif
    </label>

    @if ($field->field_type === \App\Models\FormField::TYPE_TEXTAREA)
        <textarea name="{{ $name }}" rows="3" @required($field->is_required) @readonly($readonly)
            class="{{ $inputClasses }}">{{ $selected }}</textarea>
    @elseif ($field->field_type === \App\Models\FormField::TYPE_DATE)
        <input type="date" name="{{ $name }}" value="{{ $selected }}" @required($field->is_required) @readonly($readonly) class="{{ $inputClasses }}">
    @elseif ($field->field_type === \App\Models\FormField::TYPE_FILE)
        <input type="file" name="{{ $name }}" @required($field->is_required) @disabled($readonly) class="{{ $inputClasses }}">
    @elseif ($field->field_type === \App\Models\FormField::TYPE_SELECT)
        <select name="{{ $name }}" @required($field->is_required) @disabled($readonly) class="{{ $inputClasses }} bg-white">
            <option value="">{{ __('Select...') }}</option>
            @foreach ($field->options as $option)
                <option value="{{ $option->value }}" @selected($selected === $option->value)>{{ $option->value }}</option>
            @endforeach
        </select>
    @elseif (in_array($field->field_type, [\App\Models\FormField::TYPE_CHECKBOX, \App\Models\FormField::TYPE_RADIO, \App\Models\FormField::TYPE_PICK_N]))
        <div class="space-y-1.5">
            @php $inputType = $field->field_type === \App\Models\FormField::TYPE_RADIO ? 'radio' : 'checkbox'; @endphp
            @foreach ($field->options as $option)
                <label class="flex items-center gap-2 text-sm">
                    <input type="{{ $inputType }}" name="{{ $name }}" value="{{ $option->value }}"
                        @checked(is_array($selected) && in_array($option->value, $selected)) @disabled($readonly)>
                    {{ $option->value }}
                </label>
            @endforeach
        </div>
    @else
        <input type="text" name="{{ $name }}" value="{{ $selected }}" @required($field->is_required) @readonly($readonly) class="{{ $inputClasses }}">
    @endif

    @if ($field->help_text)
        <p class="text-xs text-gray-500 mt-1">{{ $field->help_text }}</p>
    @endif
    @if (isset($errors) && $errors->has("values.{$field->key}"))
        <p class="text-brand-red text-sm mt-1">{{ $errors->first("values.{$field->key}") }}</p>
    @endif
</div>
