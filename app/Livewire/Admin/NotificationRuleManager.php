<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Group;
use App\Models\NotificationRule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Administración de reglas de notificación (evento + categoría → grupo,
 * canal) — docs/SPEC_DESARROLLO.md sección 8.1. Requiere permiso
 * notification_rules.manage.
 */
class NotificationRuleManager extends Component
{
    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        $this->authorize('notification_rules.manage');
    }

    public function newRule(): void
    {
        $this->authorize('notification_rules.manage');

        $this->form = [
            'id' => null,
            'event' => 'created',
            'category_id' => null,
            'related_to_group_id' => null,
            'group_id' => null,
            'channel' => 'both',
            'is_active' => true,
        ];
        $this->showForm = true;
    }

    public function editRule(int $ruleId): void
    {
        $this->authorize('notification_rules.manage');

        $rule = NotificationRule::findOrFail($ruleId);
        $this->form = [
            'id' => $rule->id,
            'event' => $rule->event,
            'category_id' => $rule->category_id,
            'related_to_group_id' => $rule->related_to_group_id,
            'group_id' => $rule->group_id,
            'channel' => $rule->channel,
            'is_active' => $rule->is_active,
        ];
        $this->showForm = true;
    }

    public function saveRule(): void
    {
        $this->authorize('notification_rules.manage');

        $data = $this->validate([
            'form.event' => ['required', 'in:created,status_changed,reassigned,comment_added,sla_warning,sla_breached,agreement_signed'],
            'form.category_id' => ['nullable', 'exists:categories,id'],
            'form.related_to_group_id' => ['nullable', 'exists:groups,id'],
            'form.group_id' => ['required', 'exists:groups,id'],
            'form.channel' => ['required', 'in:email,platform,both'],
            'form.is_active' => ['boolean'],
        ])['form'];

        NotificationRule::updateOrCreate(
            ['id' => $this->form['id']],
            [
                'event' => $data['event'],
                'category_id' => $data['category_id'],
                'related_to_group_id' => $data['related_to_group_id'],
                'group_id' => $data['group_id'],
                'channel' => $data['channel'],
                'is_active' => (bool) $data['is_active'],
            ]
        );

        $this->showForm = false;
    }

    public function toggleActive(int $ruleId): void
    {
        $this->authorize('notification_rules.manage');

        $rule = NotificationRule::findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function deleteRule(int $ruleId): void
    {
        $this->authorize('notification_rules.manage');

        NotificationRule::where('id', $ruleId)->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.notification-rule-manager', [
            'rules' => NotificationRule::with(['category', 'relatedToGroup', 'group'])->orderBy('event')->get(),
            'categories' => Category::orderBy('name')->get(),
            'groups' => Group::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
