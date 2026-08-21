<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Issue;
use App\Models\TicketType;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Explorador de solo lectura del catálogo Categoría → Issue → Campos,
 * para verificar que el catálogo sembrado (docs/SPEC_DESARROLLO.md
 * secciones 4 y 5) quedó correcto antes de construir el formulario de
 * creación de tickets.
 */
class CatalogBrowser extends Component
{
    public string $ticketTypeCode = TicketType::RETAILER;

    public ?int $categoryId = null;

    public ?int $issueId = null;

    public function mount(): void
    {
        $this->authorize('catalog.manage');

        $this->categoryId = Category::whereHas('ticketType', fn ($q) => $q->where('code', $this->ticketTypeCode))
            ->orderBy('sort_order')
            ->value('id');
    }

    public function selectType(string $code): void
    {
        $this->ticketTypeCode = $code;
        $this->categoryId = Category::whereHas('ticketType', fn ($q) => $q->where('code', $code))
            ->orderBy('sort_order')
            ->value('id');
        $this->issueId = null;
    }

    public function selectCategory(int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->issueId = null;
    }

    public function selectIssue(int $issueId): void
    {
        $this->issueId = $issueId;
    }

    public function render(): View
    {
        $ticketTypes = TicketType::orderBy('code')->get();

        $categories = Category::with('issues')
            ->whereHas('ticketType', fn ($q) => $q->where('code', $this->ticketTypeCode))
            ->orderBy('sort_order')
            ->get();

        $issues = $this->categoryId
            ? Issue::where('category_id', $this->categoryId)->orderBy('sort_order')->get()
            : collect();

        $selectedIssue = $this->issueId
            ? Issue::with('fieldDefinitions.options')->find($this->issueId)
            : null;

        return view('livewire.admin.catalog-browser', [
            'ticketTypes' => $ticketTypes,
            'categories' => $categories,
            'issues' => $issues,
            'selectedIssue' => $selectedIssue,
        ]);
    }
}
