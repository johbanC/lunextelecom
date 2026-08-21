<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Ticket catalog') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Category → Issue → Fields, as loaded from the seed data.') }}</p>
        </div>
        <div class="inline-flex p-1 rounded-full bg-gray-100 border border-gray-200">
            @foreach ($ticketTypes as $type)
                <button wire:click="selectType('{{ $type->code }}')"
                    class="px-4 py-1.5 rounded-full font-semibold text-sm transition
                        {{ $ticketTypeCode === $type->code ? 'bg-brand-blue text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $type->name }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {{ __('Categories') }}
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <li>
                        <button wire:click="selectCategory({{ $category->id }})"
                            class="w-full text-left px-4 py-3 text-sm flex items-center justify-between transition
                                {{ $categoryId === $category->id ? 'bg-brand-blue-50 text-brand-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            {{ $category->name }}
                            <span class="text-xs text-gray-400">{{ $category->issues->count() }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('No categories.') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {{ __('Issues') }}
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($issues as $issue)
                    <li>
                        <button wire:click="selectIssue({{ $issue->id }})"
                            class="w-full text-left px-4 py-3 text-sm transition
                                {{ $issueId === $issue->id ? 'bg-brand-blue-50 text-brand-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                            {{ $issue->name }}
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('Select a category.') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {{ __('Fields') }}
            </div>
            @if ($selectedIssue)
                <ul class="divide-y divide-gray-100">
                    @foreach ($selectedIssue->fieldDefinitions as $field)
                        <li class="px-4 py-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-gray-700">{{ $field->label }}</span>
                                @if ($field->is_required)
                                    <span class="text-[10px] font-bold uppercase text-brand-red">{{ __('Required') }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $field->field_type }}@if ($field->pick_count) &middot; {{ __('Pick :n', ['n' => $field->pick_count]) }} @endif
                            </div>
                            @if ($field->options->isNotEmpty())
                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    @foreach ($field->options as $option)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[11px]">{{ $option->value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($selectedIssue->fieldDefinitions->isEmpty())
                    <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('This issue has no extra fields.') }}</p>
                @endif
            @else
                <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __('Select an issue to see its fields.') }}</p>
            @endif
        </div>
    </div>
</div>
