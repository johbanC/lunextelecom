<?php

namespace App\Livewire\Admin;

use App\Models\Group;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Administración de grupos ("Related To") y su membresía —
 * docs/SPEC_DESARROLLO.md sección 6 (requerimiento de grupos multi-tipo,
 * campo applies_to) y sección 7 (Admin "crea/edita grupos"). Requiere
 * permiso groups.manage.
 */
class GroupManager extends Component
{
    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public ?int $membersGroupId = null;

    public string $newMemberUserId = '';

    public string $newMemberRole = 'member';

    public function mount(): void
    {
        $this->authorize('groups.manage');
    }

    public function newGroup(): void
    {
        $this->authorize('groups.manage');

        $this->form = ['id' => null, 'name' => '', 'applies_to' => 'both'];
        $this->showForm = true;
    }

    public function editGroup(int $groupId): void
    {
        $this->authorize('groups.manage');

        $group = Group::findOrFail($groupId);
        $this->form = ['id' => $group->id, 'name' => $group->name, 'applies_to' => $group->applies_to];
        $this->showForm = true;
    }

    public function saveGroup(): void
    {
        $this->authorize('groups.manage');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.applies_to' => ['required', 'in:retailer,customer,both'],
        ])['form'];

        Group::updateOrCreate(
            ['id' => $this->form['id']],
            ['name' => $data['name'], 'applies_to' => $data['applies_to']]
        );

        $this->showForm = false;
    }

    public function toggleActive(int $groupId): void
    {
        $this->authorize('groups.manage');

        $group = Group::findOrFail($groupId);
        $group->update(['is_active' => ! $group->is_active]);
    }

    public function manageMembers(int $groupId): void
    {
        $this->authorize('groups.manage');

        $this->membersGroupId = $groupId;
        $this->newMemberUserId = '';
        $this->newMemberRole = 'member';
    }

    public function addMember(): void
    {
        $this->authorize('groups.manage');

        if (! $this->newMemberUserId || ! $this->membersGroupId) {
            return;
        }

        $group = Group::findOrFail($this->membersGroupId);
        $group->members()->syncWithoutDetaching([
            (int) $this->newMemberUserId => ['role_in_group' => $this->newMemberRole],
        ]);

        $this->newMemberUserId = '';
        $this->newMemberRole = 'member';
    }

    public function updateMemberRole(int $userId, string $role): void
    {
        $this->authorize('groups.manage');

        if (! in_array($role, ['member', 'leader'], true) || ! $this->membersGroupId) {
            return;
        }

        Group::findOrFail($this->membersGroupId)->members()->updateExistingPivot($userId, ['role_in_group' => $role]);
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('groups.manage');

        if (! $this->membersGroupId) {
            return;
        }

        Group::findOrFail($this->membersGroupId)->members()->detach($userId);
    }

    public function render(): View
    {
        return view('livewire.admin.group-manager', [
            'groups' => Group::withCount('members')->orderBy('name')->get(),
            'membersGroup' => $this->membersGroupId
                ? Group::with('members')->find($this->membersGroupId)
                : null,
            'allUsers' => User::orderBy('name')->get(),
        ]);
    }
}
