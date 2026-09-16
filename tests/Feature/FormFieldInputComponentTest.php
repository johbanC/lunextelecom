<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormFieldInputComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_text_input_with_the_field_key_as_name(): void
    {
        $user = User::factory()->create();
        $template = FormTemplate::create(['name' => 'T', 'mode' => 'standalone', 'slug' => 't', 'created_by' => $user->id]);
        $field = $template->fields()->create(['label' => 'Full Name', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $html = (string) view('components.form-field-input', ['field' => $field, 'value' => 'Katherine', 'readonly' => false])->render();

        $this->assertStringContainsString('name="values[full_name]"', $html);
        $this->assertStringContainsString('value="Katherine"', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function test_readonly_fields_are_disabled_for_the_recipient(): void
    {
        $user = User::factory()->create();
        $template = FormTemplate::create(['name' => 'T', 'mode' => 'on_demand', 'created_by' => $user->id]);
        $field = $template->fields()->create(['label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);

        $html = (string) view('components.form-field-input', ['field' => $field, 'value' => '6688', 'readonly' => true])->render();

        $this->assertStringContainsString('readonly', $html);
    }

    public function test_it_displays_a_validation_error_for_the_field(): void
    {
        $user = User::factory()->create();
        $template = FormTemplate::create(['name' => 'T2', 'mode' => 'standalone', 'slug' => 't2', 'created_by' => $user->id]);
        $field = $template->fields()->create(['label' => 'Full Name', 'field_type' => FormField::TYPE_TEXT]);

        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['values.full_name' => ['This field is required.']]));

        $html = (string) view('components.form-field-input', ['field' => $field, 'value' => '', 'readonly' => false])
            ->with('errors', $errors)
            ->render();

        $this->assertStringContainsString('This field is required.', $html);
    }
}
