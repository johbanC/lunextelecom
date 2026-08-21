@props(['status'])

@php
$labels = [
    'green' => __('On time'),
    'yellow' => __('Due soon'),
    'red' => __('Overdue'),
    'done' => __('Done'),
];
@endphp

<span @class([
    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold',
    'bg-emerald-100 text-emerald-700' => $status === 'green',
    'bg-amber-100 text-amber-700' => $status === 'yellow',
    'bg-red-100 text-red-700' => $status === 'red',
    'bg-gray-100 text-gray-500' => $status === 'done',
])>
    <span @class([
        'size-1.5 rounded-full',
        'bg-emerald-500' => $status === 'green',
        'bg-amber-500' => $status === 'yellow',
        'bg-red-500' => $status === 'red',
        'bg-gray-400' => $status === 'done',
    ])></span>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
