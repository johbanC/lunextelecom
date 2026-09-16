<?php

namespace App\Livewire\Admin;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormTemplate;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class FormTemplateManager extends Component
{
    public ?int $templateId = null;

    public bool $showTemplateForm = false;

    /** @var array<string, mixed> */
    public array $templateForm = [
        'id' => null,
        'name' => '',
        'slug' => '',
        'instructions' => '',
        'requires_signature' => false,
        'mode' => FormTemplate::MODE_ON_DEMAND,
        'notify_group_id' => null,
        'is_active' => true,
    ];

    public bool $showFieldForm = false;

    /** @var array<string, mixed> */
    public array $fieldForm = [
        'id' => null,
        'label' => '',
        'field_type' => FormField::TYPE_TEXT,
        'is_required' => false,
        'editable_by_recipient' => true,
        'help_text' => '',
        'pick_count' => null,
        'sort_order' => 0,
        'options' => [],
    ];

    public string $newOptionValue = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FormTemplate::class);
    }

    public function selectTemplate(int $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function newTemplate(): void
    {
        $this->authorize('create', FormTemplate::class);

        $this->templateForm = [
            'id' => null,
            'name' => '',
            'slug' => '',
            'instructions' => '',
            'requires_signature' => false,
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'notify_group_id' => null,
            'is_active' => true,
        ];
        $this->showTemplateForm = true;
    }

    public function editTemplate(int $templateId): void
    {
        $template = FormTemplate::findOrFail($templateId);

        $this->templateForm = [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'instructions' => $template->instructions,
            'requires_signature' => $template->requires_signature,
            'mode' => $template->mode,
            'notify_group_id' => $template->notify_group_id,
            'is_active' => $template->is_active,
        ];
        $this->showTemplateForm = true;
    }

    public function saveTemplate(): void
    {
        $this->authorize(($this->templateForm['id'] ?? null) ? 'update' : 'create', FormTemplate::class);

        $data = $this->validate([
            'templateForm.name' => ['required', 'string', 'max:255'],
            'templateForm.mode' => ['required', 'in:'.FormTemplate::MODE_ON_DEMAND.','.FormTemplate::MODE_STANDALONE],
            'templateForm.slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                'required_if:templateForm.mode,'.FormTemplate::MODE_STANDALONE,
                'unique:form_templates,slug,'.($this->templateForm['id'] ?? 'NULL'),
            ],
            'templateForm.instructions' => ['nullable', 'string', 'max:2000'],
            'templateForm.requires_signature' => ['boolean'],
            'templateForm.notify_group_id' => ['nullable', 'exists:groups,id'],
            'templateForm.is_active' => ['boolean'],
        ])['templateForm'];

        $template = FormTemplate::updateOrCreate(
            ['id' => $this->templateForm['id'] ?? null],
            [
                'name' => $data['name'],
                'slug' => $data['mode'] === FormTemplate::MODE_STANDALONE ? $data['slug'] : null,
                'instructions' => $data['instructions'] ?: null,
                'requires_signature' => (bool) $data['requires_signature'],
                'mode' => $data['mode'],
                'notify_group_id' => $data['notify_group_id'],
                'is_active' => (bool) $data['is_active'],
                'created_by' => ($this->templateForm['id'] ?? null) ? FormTemplate::find($this->templateForm['id'])->created_by : Auth::id(),
            ]
        );

        $this->templateId = $template->id;
        $this->showTemplateForm = false;
    }

    public function newField(): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $this->fieldForm = [
            'id' => null,
            'label' => '',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => false,
            'editable_by_recipient' => true,
            'help_text' => '',
            'pick_count' => null,
            'sort_order' => FormField::where('form_template_id', $this->templateId)->count(),
            'options' => [],
        ];
        $this->showFieldForm = true;
    }

    public function editField(int $fieldId): void
    {
        $field = FormField::with('options')->findOrFail($fieldId);
        $this->fieldForm = [
            'id' => $field->id,
            'label' => $field->label,
            'field_type' => $field->field_type,
            'is_required' => $field->is_required,
            'editable_by_recipient' => $field->editable_by_recipient,
            'help_text' => $field->help_text ?? '',
            'pick_count' => $field->pick_count,
            'sort_order' => $field->sort_order,
            'options' => $field->options->pluck('value', 'id')->all(),
        ];
        $this->showFieldForm = true;
    }

    public function saveField(): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $data = $this->validate([
            'fieldForm.label' => ['required', 'string', 'max:255'],
            'fieldForm.field_type' => ['required', 'in:'.implode(',', [
                FormField::TYPE_TEXT, FormField::TYPE_TEXTAREA, FormField::TYPE_SELECT,
                FormField::TYPE_CHECKBOX, FormField::TYPE_RADIO, FormField::TYPE_PICK_N,
                FormField::TYPE_DATE, FormField::TYPE_FILE,
            ])],
            'fieldForm.is_required' => ['boolean'],
            'fieldForm.editable_by_recipient' => ['boolean'],
            'fieldForm.help_text' => ['nullable', 'string', 'max:500'],
            'fieldForm.pick_count' => ['nullable', 'integer', 'min:1', 'max:10', 'required_if:fieldForm.field_type,'.FormField::TYPE_PICK_N],
            'fieldForm.sort_order' => ['required', 'integer', 'min:0'],
        ])['fieldForm'];

        $field = FormField::updateOrCreate(
            ['id' => $this->fieldForm['id']],
            [
                'form_template_id' => $this->templateId,
                'label' => $data['label'],
                'field_type' => $data['field_type'],
                'is_required' => (bool) $data['is_required'],
                'editable_by_recipient' => (bool) $data['editable_by_recipient'],
                'help_text' => $data['help_text'] ?: null,
                'pick_count' => $data['field_type'] === FormField::TYPE_PICK_N ? $data['pick_count'] : null,
                'sort_order' => $data['sort_order'],
            ]
        );

        $needsOptions = in_array($data['field_type'], [
            FormField::TYPE_SELECT, FormField::TYPE_CHECKBOX, FormField::TYPE_RADIO, FormField::TYPE_PICK_N,
        ], true);

        if ($needsOptions) {
            $this->editField($field->id);
        } else {
            $this->showFieldForm = false;
        }
    }

    public function deleteField(int $fieldId): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $field = FormField::withCount('submissionValues')->findOrFail($fieldId);

        if ($field->submission_values_count > 0) {
            $this->addError('fieldForm.label', __('This field already has answers on existing submissions and cannot be deleted. Deactivate the template instead if it is no longer used.'));

            return;
        }

        $field->delete();
        $this->showFieldForm = false;
    }

    public function moveField(int $fieldId, string $direction): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $fields = FormField::where('form_template_id', $this->templateId)->orderBy('sort_order')->get();

        $this->reorder($fields, $fieldId, $direction);
    }

    public function addFieldOption(): void
    {
        $value = trim($this->newOptionValue);

        if ($value === '' || ! $this->fieldForm['id']) {
            return;
        }

        $option = FormFieldOption::create([
            'form_field_id' => $this->fieldForm['id'],
            'value' => $value,
            'sort_order' => count($this->fieldForm['options']),
        ]);

        $this->fieldForm['options'][$option->id] = $option->value;
        $this->newOptionValue = '';
    }

    public function removeFieldOption(int $optionId): void
    {
        FormFieldOption::where('id', $optionId)->delete();
        unset($this->fieldForm['options'][$optionId]);
    }

    /**
     * @param  Collection<int, Model>  $items
     */
    protected function reorder(Collection $items, int $id, string $direction): void
    {
        $items = $items->values();

        $items->each(function (Model $item, int $index) {
            if ($item->sort_order !== $index) {
                $item->update(['sort_order' => $index]);
            }
        });

        $index = $items->search(fn (Model $item) => $item->id === $id);

        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWith < 0 || $swapWith >= $items->count()) {
            return;
        }

        $items[$index]->update(['sort_order' => $swapWith]);
        $items[$swapWith]->update(['sort_order' => $index]);
    }

    public function render(): View
    {
        $templates = FormTemplate::withCount('fields')->orderBy('name')->get();

        $selectedTemplate = $this->templateId
            ? FormTemplate::with('fields.options')->find($this->templateId)
            : null;

        return view('livewire.admin.form-template-manager', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'groups' => Group::where('is_active', true)->orderBy('name')->get(),
            'fieldTypes' => [
                FormField::TYPE_TEXT => __('Text'),
                FormField::TYPE_TEXTAREA => __('Textarea'),
                FormField::TYPE_SELECT => __('Select'),
                FormField::TYPE_CHECKBOX => __('Checkbox (multiple)'),
                FormField::TYPE_RADIO => __('Radio (single)'),
                FormField::TYPE_PICK_N => __('Pick N'),
                FormField::TYPE_DATE => __('Date'),
                FormField::TYPE_FILE => __('File'),
            ],
        ]);
    }
}
