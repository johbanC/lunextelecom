<?php

namespace Tests\Feature\Admin;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_generate_an_on_demand_link_with_known_values(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'is_active' => true,
            'created_by' => $agente->id,
        ]);
        $known = $template->fields()->create([
            'label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false,
        ]);

        $response = $this->actingAs($agente)->post(route('admin.forms.store'), [
            'form_template_id' => $template->id,
            'expires_in' => '7',
            'known_values' => [$known->id => '6688'],
        ]);

        $submission = $template->submissions()->firstOrFail();
        $response->assertRedirect(route('admin.forms.show', $submission));
        $this->assertEquals('6688', $submission->values()->where('form_field_id', $known->id)->first()->value);
        $this->assertEquals('pending', $submission->status);
    }

    public function test_forms_index_lists_submissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $response = $this->actingAs($agente)->get(route('admin.forms.index'));

        $response->assertOk();
    }
}
