<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\FieldDefinition;
use App\Models\FieldOption;
use App\Models\Group;
use App\Models\Issue;
use App\Models\TicketType;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Catálogo Categoría → Issue → Campos, administrable desde el Admin
 * (docs/SPEC_DESARROLLO.md sección 3: "poder establecer nuevos Issues"
 * sin tocar código). Requiere permiso catalog.manage.
 */
class CatalogBrowser extends Component
{
    public string $ticketTypeCode = TicketType::RETAILER;

    public ?int $categoryId = null;

    public ?int $issueId = null;

    public bool $showCategoryForm = false;

    /** @var array<string, mixed> */
    public array $categoryForm = [];

    public bool $showIssueForm = false;

    /** @var array<string, mixed> */
    public array $issueForm = [];

    public bool $showFieldForm = false;

    /** @var array<string, mixed> */
    public array $fieldForm = [];

    public string $newOptionValue = '';

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

    /*
    |--------------------------------------------------------------------
    | Categorías
    |--------------------------------------------------------------------
    */

    public function newCategory(): void
    {
        $this->categoryForm = [
            'id' => null,
            'name' => '',
            'default_related_to_group_id' => null,
            'sla_yellow_days' => 2,
            'sla_red_days' => 3,
            'sort_order' => 0,
        ];
        $this->showCategoryForm = true;
    }

    public function editCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $this->categoryForm = [
            'id' => $category->id,
            'name' => $category->name,
            'default_related_to_group_id' => $category->default_related_to_group_id,
            'sla_yellow_days' => $category->sla_yellow_days,
            'sla_red_days' => $category->sla_red_days,
            'sort_order' => $category->sort_order,
        ];
        $this->showCategoryForm = true;
    }

    public function saveCategory(): void
    {
        $this->authorize('catalog.manage');

        $data = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:255'],
            'categoryForm.default_related_to_group_id' => ['nullable', 'exists:groups,id'],
            'categoryForm.sla_yellow_days' => ['required', 'integer', 'min:1', 'max:60'],
            'categoryForm.sla_red_days' => ['required', 'integer', 'min:1', 'max:60', 'gte:categoryForm.sla_yellow_days'],
            'categoryForm.sort_order' => ['required', 'integer', 'min:0'],
        ])['categoryForm'];

        $ticketType = TicketType::where('code', $this->ticketTypeCode)->firstOrFail();

        $category = Category::updateOrCreate(
            ['id' => $this->categoryForm['id']],
            [
                'ticket_type_id' => $ticketType->id,
                'name' => $data['name'],
                'default_related_to_group_id' => $data['default_related_to_group_id'],
                'sla_yellow_days' => $data['sla_yellow_days'],
                'sla_red_days' => $data['sla_red_days'],
                'sort_order' => $data['sort_order'],
            ]
        );

        $this->categoryId = $category->id;
        $this->showCategoryForm = false;
    }

    public function toggleCategoryActive(int $categoryId): void
    {
        $this->authorize('catalog.manage');

        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    /*
    |--------------------------------------------------------------------
    | Issues
    |--------------------------------------------------------------------
    */

    public function newIssue(): void
    {
        $this->issueForm = ['id' => null, 'name' => '', 'sort_order' => 0];
        $this->showIssueForm = true;
    }

    public function editIssue(int $issueId): void
    {
        $issue = Issue::findOrFail($issueId);
        $this->issueForm = ['id' => $issue->id, 'name' => $issue->name, 'sort_order' => $issue->sort_order];
        $this->showIssueForm = true;
    }

    public function saveIssue(): void
    {
        $this->authorize('catalog.manage');

        $data = $this->validate([
            'issueForm.name' => ['required', 'string', 'max:255'],
            'issueForm.sort_order' => ['required', 'integer', 'min:0'],
        ])['issueForm'];

        $issue = Issue::updateOrCreate(
            ['id' => $this->issueForm['id']],
            [
                'category_id' => $this->categoryId,
                'name' => $data['name'],
                'sort_order' => $data['sort_order'],
                'created_by' => $this->issueForm['id'] ? Issue::find($this->issueForm['id'])->created_by : Auth::id(),
            ]
        );

        $this->issueId = $issue->id;
        $this->showIssueForm = false;
    }

    public function toggleIssueActive(int $issueId): void
    {
        $this->authorize('catalog.manage');

        $issue = Issue::findOrFail($issueId);
        $issue->update(['is_active' => ! $issue->is_active]);
    }

    /*
    |--------------------------------------------------------------------
    | Campos (Field definitions)
    |--------------------------------------------------------------------
    */

    public function newField(): void
    {
        $this->fieldForm = [
            'id' => null,
            'label' => '',
            'field_type' => FieldDefinition::TYPE_TEXT,
            'is_required' => false,
            'help_text' => '',
            'pick_count' => null,
            'sort_order' => 0,
            'options' => [],
        ];
        $this->showFieldForm = true;
    }

    public function editField(int $fieldId): void
    {
        $field = FieldDefinition::with('options')->findOrFail($fieldId);
        $this->fieldForm = [
            'id' => $field->id,
            'label' => $field->label,
            'field_type' => $field->field_type,
            'is_required' => $field->is_required,
            'help_text' => $field->help_text ?? '',
            'pick_count' => $field->pick_count,
            'sort_order' => $field->sort_order,
            'options' => $field->options->pluck('value', 'id')->all(),
        ];
        $this->showFieldForm = true;
    }

    public function saveField(): void
    {
        $this->authorize('catalog.manage');

        $data = $this->validate([
            'fieldForm.label' => ['required', 'string', 'max:255'],
            'fieldForm.field_type' => ['required', 'in:'.implode(',', [
                FieldDefinition::TYPE_TEXT,
                FieldDefinition::TYPE_TEXTAREA,
                FieldDefinition::TYPE_SELECT,
                FieldDefinition::TYPE_CHECKBOX,
                FieldDefinition::TYPE_RADIO,
                FieldDefinition::TYPE_PICK_N,
                FieldDefinition::TYPE_DATE,
                FieldDefinition::TYPE_FILE,
            ])],
            'fieldForm.is_required' => ['boolean'],
            'fieldForm.help_text' => ['nullable', 'string', 'max:500'],
            'fieldForm.pick_count' => ['nullable', 'integer', 'min:1', 'max:10', 'required_if:fieldForm.field_type,'.FieldDefinition::TYPE_PICK_N],
            'fieldForm.sort_order' => ['required', 'integer', 'min:0'],
        ])['fieldForm'];

        $field = FieldDefinition::updateOrCreate(
            ['id' => $this->fieldForm['id']],
            [
                'issue_id' => $this->issueId,
                'label' => $data['label'],
                'field_type' => $data['field_type'],
                'is_required' => (bool) $data['is_required'],
                'help_text' => $data['help_text'] ?: null,
                'pick_count' => $data['field_type'] === FieldDefinition::TYPE_PICK_N ? $data['pick_count'] : null,
                'sort_order' => $data['sort_order'],
            ]
        );

        $this->showFieldForm = false;
        $this->editField($field->id);
    }

    public function deleteField(int $fieldId): void
    {
        $this->authorize('catalog.manage');

        $field = FieldDefinition::withCount('ticketFieldValues')->findOrFail($fieldId);

        if ($field->ticket_field_values_count > 0) {
            $this->addError('fieldForm.label', __('This field already has answers on existing tickets and cannot be deleted. Deactivate the issue instead if it is no longer used.'));

            return;
        }

        $field->delete();
        $this->showFieldForm = false;
    }

    public function addFieldOption(): void
    {
        $this->authorize('catalog.manage');

        $value = trim($this->newOptionValue);

        if ($value === '' || ! $this->fieldForm['id']) {
            return;
        }

        $option = FieldOption::create([
            'field_definition_id' => $this->fieldForm['id'],
            'value' => $value,
            'sort_order' => count($this->fieldForm['options']),
        ]);

        $this->fieldForm['options'][$option->id] = $option->value;
        $this->newOptionValue = '';
    }

    public function removeFieldOption(int $optionId): void
    {
        $this->authorize('catalog.manage');

        FieldOption::where('id', $optionId)->delete();
        unset($this->fieldForm['options'][$optionId]);
    }

    public function render(): View
    {
        $ticketTypes = TicketType::orderBy('code')->get();

        $categories = Category::withCount('issues')
            ->whereHas('ticketType', fn ($q) => $q->where('code', $this->ticketTypeCode))
            ->orderBy('sort_order')
            ->get();

        $issues = $this->categoryId
            ? Issue::where('category_id', $this->categoryId)->orderBy('sort_order')->get()
            : collect();

        $selectedIssue = $this->issueId
            ? Issue::with('fieldDefinitions.options')->find($this->issueId)
            : null;

        $groups = Group::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Group $group) => $group->appliesTo($this->ticketTypeCode));

        return view('livewire.admin.catalog-browser', [
            'ticketTypes' => $ticketTypes,
            'categories' => $categories,
            'issues' => $issues,
            'selectedIssue' => $selectedIssue,
            'groups' => $groups,
            'fieldTypes' => [
                FieldDefinition::TYPE_TEXT => __('Text'),
                FieldDefinition::TYPE_TEXTAREA => __('Textarea'),
                FieldDefinition::TYPE_SELECT => __('Select'),
                FieldDefinition::TYPE_CHECKBOX => __('Checkbox (multiple)'),
                FieldDefinition::TYPE_RADIO => __('Radio (single)'),
                FieldDefinition::TYPE_PICK_N => __('Pick N'),
                FieldDefinition::TYPE_DATE => __('Date'),
                FieldDefinition::TYPE_FILE => __('File'),
            ],
        ]);
    }
}
