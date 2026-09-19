<?php

namespace Tests\Feature;

use App\Livewire\Admin\FormTemplateManager;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormTemplateManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_template_and_add_a_field(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        Livewire::test(FormTemplateManager::class)
            ->set('templateForm.name', 'Potential Retailer Sign Up')
            ->set('templateForm.mode', FormTemplate::MODE_STANDALONE)
            ->set('templateForm.slug', 'potential-retailer')
            ->set('templateForm.requires_signature', false)
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $template = FormTemplate::firstOrFail();
        $this->assertEquals('Potential Retailer Sign Up', $template->name);

        Livewire::test(FormTemplateManager::class)
            ->call('selectTemplate', $template->id)
            ->call('newField')
            ->set('fieldForm.label', 'Full Name')
            ->set('fieldForm.field_type', 'text')
            ->call('saveField')
            ->assertHasNoErrors();

        $this->assertEquals(1, $template->fields()->count());
        $this->assertEquals('Full Name', $template->fields()->first()->label);
    }

    public function test_admin_can_edit_and_save_an_existing_template(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'display_mode' => FormTemplate::DISPLAY_NARRATIVE,
            'instructions' => 'Hello, {{full_name}}.',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        Livewire::test(FormTemplateManager::class)
            ->call('selectTemplate', $template->id)
            ->call('editTemplate', $template->id)
            ->set('templateForm.instructions', 'Hello, {{full_name}}, your account {{account_id}} was updated.')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertEquals(
            'Hello, {{full_name}}, your account {{account_id}} was updated.',
            $template->fresh()->instructions
        );
    }

    public function test_agente_cannot_access_the_template_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');
        $this->actingAs($agente);

        Livewire::test(FormTemplateManager::class)->assertForbidden();
    }
}
