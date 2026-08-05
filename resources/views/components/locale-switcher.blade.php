@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-gray-100 border border-gray-200 p-0.5 text-xs font-bold ' . $class]) }}>
    @foreach (['en' => 'EN', 'es' => 'ES'] as $locale => $label)
        <a href="{{ route('locale.switch', $locale) }}"
            class="px-2.5 py-1 rounded-full transition {{ app()->getLocale() === $locale ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
