<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_submission_stores_values_per_field_and_reports_expiry(): void
    {
        $user = User::factory()->create();

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $field = $template->fields()->create([
            'label' => 'New phone number',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => true,
            'editable_by_recipient' => false,
        ]);

        $submission = FormSubmission::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $submission->values()->create(['form_field_id' => $field->id, 'value' => '+19294134764']);

        $this->assertTrue($submission->isExpired());
        $this->assertFalse($submission->isSubmitted());
        $this->assertEquals('+19294134764', $submission->values()->first()->value);
        $this->assertTrue($submission->values()->first()->field->is($field));
        $this->assertStringContainsString($submission->uuid, $submission->publicUrl());
    }

    public function test_interpolated_instructions_replaces_placeholders_with_field_values(): void
    {
        $user = User::factory()->create();

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'display_mode' => FormTemplate::DISPLAY_NARRATIVE,
            'instructions' => 'Hello, {{full_name}}, your account {{account_id}} was updated. Unknown: {{missing}}.',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $nameField = $template->fields()->create(['label' => 'Full name', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);
        $accountField = $template->fields()->create(['label' => 'Account id', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);

        $submission = FormSubmission::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
        $submission->values()->create(['form_field_id' => $nameField->id, 'value' => 'Mr. Patel']);
        $submission->values()->create(['form_field_id' => $accountField->id, 'value' => 'COAM32D132']);

        $this->assertTrue($template->isNarrative());
        $this->assertEquals(
            'Hello, Mr. Patel, your account COAM32D132 was updated. Unknown: {{missing}}.',
            $submission->interpolatedInstructions()
        );
    }
}
