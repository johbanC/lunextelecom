<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\HelpArticle;
use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Centro de ayuda — docs/SPEC_DESARROLLO.md sección 8.9: guías por
 * categoría + FAQ (HelpArticle, administrable con permiso help.manage) y un
 * glosario de Issues armado a partir de los help_text ya cargados en el
 * catálogo (sin datos nuevos que mantener por separado).
 */
class HelpCenter extends Component
{
    public string $search = '';

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('tickets.view.own'), 403);
    }

    public function newArticle(): void
    {
        $this->authorize('help.manage');

        $this->form = ['id' => null, 'category_id' => '', 'title' => '', 'body' => '', 'sort_order' => 0];
        $this->showForm = true;
    }

    public function editArticle(int $articleId): void
    {
        $this->authorize('help.manage');

        $article = HelpArticle::findOrFail($articleId);
        $this->form = [
            'id' => $article->id,
            'category_id' => $article->category_id,
            'title' => $article->title,
            'body' => $article->body,
            'sort_order' => $article->sort_order,
        ];
        $this->showForm = true;
    }

    public function saveArticle(): void
    {
        $this->authorize('help.manage');

        $data = $this->validate([
            'form.category_id' => ['nullable', 'exists:categories,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.body' => ['required', 'string', 'max:10000'],
            'form.sort_order' => ['required', 'integer', 'min:0'],
        ])['form'];

        HelpArticle::updateOrCreate(
            ['id' => $this->form['id']],
            [
                'category_id' => $data['category_id'] ?: null,
                'title' => $data['title'],
                'body' => $data['body'],
                'sort_order' => $data['sort_order'],
                'created_by' => $this->form['id'] ? HelpArticle::find($this->form['id'])->created_by : Auth::id(),
            ]
        );

        $this->showForm = false;
    }

    public function toggleActive(int $articleId): void
    {
        $this->authorize('help.manage');

        $article = HelpArticle::findOrFail($articleId);
        $article->update(['is_active' => ! $article->is_active]);
    }

    public function render(): View
    {
        $canManage = Auth::user()->can('help.manage');

        $articles = HelpArticle::query()
            ->when(! $canManage, fn ($q) => $q->where('is_active', true))
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('body', 'like', '%'.$this->search.'%')
            ))
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (HelpArticle $article) => $article->category?->name ?? __('General'));

        $issueGlossary = Issue::query()
            ->with(['category', 'fieldDefinitions' => fn ($q) => $q->whereNotNull('help_text')->where('help_text', '!=', '')])
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->get()
            ->filter(fn (Issue $issue) => $issue->fieldDefinitions->isNotEmpty())
            ->groupBy(fn (Issue $issue) => $issue->category->name);

        return view('livewire.admin.help-center', [
            'articles' => $articles,
            'issueGlossary' => $issueGlossary,
            'categories' => Category::orderBy('name')->get(),
            'canManage' => $canManage,
        ]);
    }
}
