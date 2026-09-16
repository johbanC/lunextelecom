<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicFormOnDemandTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_view_and_submit_a_pending_on_demand_form(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'created_by' => $agent->id,
        ]);
        $readonly = $template->fields()->create(['label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);
        $editable = $template->fields()->create(['label' => 'New number', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
            'created_by' => $agent->id,
        ]);
        $submission->values()->create(['form_field_id' => $readonly->id, 'value' => '6688']);

        $this->get(route('public.forms.show', $submission->uuid))->assertOk();

        $response = $this->post(route('public.forms.store', $submission->uuid), [
            'values' => [$editable->key => '9294134764'],
            'signature' => 'data:image/png;base64,'.base64_encode('fake'),
        ]);

        $submission->refresh();
        $response->assertRedirect(route('public.forms.thanks', $submission->uuid));
        $this->assertTrue($submission->isSubmitted());
        $this->assertEquals('9294134764', $submission->values()->where('form_field_id', $editable->id)->first()->value);
        $this->assertNotNull($submission->signature_path);
    }

    public function test_expired_submission_rejects_new_submits(): void
    {
        $agent = User::factory()->create();
        $template = FormTemplate::create(['name' => 'X', 'mode' => FormTemplate::MODE_ON_DEMAND, 'created_by' => $agent->id]);
        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $this->post(route('public.forms.store', $submission->uuid), ['values' => []])->assertForbidden();
    }
}
