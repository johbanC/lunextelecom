<div>
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Help center') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Guides, FAQ and a glossary of what each field means per Issue.') }}</p>
        </div>
        @if ($canManage)
            <button wire:click="newArticle" class="inline-flex items-center gap-2 bg-brand-blue text-white pl-3 pr-4 py-2 rounded-lg font-semibold text-sm shadow-sm shadow-brand-blue/30 hover:bg-brand-blue-600 active:bg-brand-blue-700 transition">
                + {{ __('New article') }}
            </button>
        @endif
    </div>

    <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search guides, FAQ and issues…') }}"
        class="w-full h-11 border border-gray-300 rounded-lg px-4 bg-white text-sm mb-6 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">

    {{-- Guías / FAQ --}}
    <div class="space-y-6 mb-8">
        @forelse ($articles as $groupName => $group)
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ $groupName }}</h2>
                <div class="space-y-3">
                    @foreach ($group as $article)
                        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 {{ ! $article->is_active ? 'opacity-50' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-gray-800">{{ $article->title }}</h3>
                                @if ($canManage)
                                    <div class="flex items-center gap-3 shrink-0">
                                        @unless ($article->is_active)
                                            <span class="text-[10px] font-bold uppercase text-gray-400">{{ __('Inactive') }}</span>
                                        @endunless
                                        <button wire:click="editArticle({{ $article->id }})" class="text-xs font-semibold text-brand-blue hover:underline">{{ __('Edit') }}</button>
                                        <button wire:click="toggleActive({{ $article->id }})" class="text-xs font-semibold text-gray-500 hover:underline">
                                            {{ $article->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ $article->body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ __('No guides published yet.') }}</p>
        @endforelse
    </div>

    {{-- Glosario de Issues --}}
    <div>
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('Issue field glossary') }}</h2>
        <p class="text-xs text-gray-400 mb-3">{{ __('Pulled automatically from the help text set on each field in the catalog.') }}</p>
        @forelse ($issueGlossary as $categoryName => $issues)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $categoryName }}</h3>
                <div class="space-y-4">
                    @foreach ($issues as $issue)
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-1">{{ $issue->name }}</div>
                            <dl class="space-y-1">
                                @foreach ($issue->fieldDefinitions as $field)
                                    <div class="text-sm">
                                        <dt class="inline font-medium text-gray-600">{{ $field->label }}:</dt>
                                        <dd class="inline text-gray-500">{{ $field->help_text }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ __('No field help text set yet — add it from the catalog to have it show up here.') }}</p>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto">
            <div class="absolute inset-0 bg-gray-500/75" wire:click="$set('showForm', false)"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $form['id'] ? __('Edit article') : __('New article') }}</h2>
                <form wire:submit="saveArticle" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Category (optional — leave blank for general FAQ)') }}</label>
                        <select wire:model="form.category_id" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm bg-white focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                            <option value="">{{ __('General') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Title') }}</label>
                        <input type="text" wire:model="form.title" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                        @error('form.title') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Body') }}</label>
                        <textarea wire:model="form.body" rows="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition"></textarea>
                        @error('form.body') <p class="text-xs text-brand-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('Sort order') }}</label>
                        <input type="number" min="0" wire:model="form.sort_order" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition">
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-blue text-white hover:bg-brand-blue-600 transition">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
