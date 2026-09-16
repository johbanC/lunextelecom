<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicFormStandaloneTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_and_submit_a_standalone_form_creating_a_new_submission_each_time(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $group = Group::create(['name' => 'Retail Onboarding', 'is_active' => true]);
        $group->members()->attach($admin->id);

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'requires_signature' => false,
            'is_active' => true,
            'notify_group_id' => $group->id,
            'created_by' => $admin->id,
        ]);
        $nameField = $template->fields()->create(['label' => 'Full Name', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $this->get(route('public.forms.standalone.show', 'potential-retailer'))->assertOk();

        $response = $this->post(route('public.forms.standalone.store', 'potential-retailer'), [
            'values' => [$nameField->key => 'Katherine Castellanos'],
        ]);

        $this->assertEquals(1, FormSubmission::count());
        $submission = FormSubmission::firstOrFail();
        $response->assertRedirect(route('public.forms.thanks', $submission->uuid));
        $this->assertTrue($submission->isSubmitted());
        $this->assertNull($submission->expires_at);

        Notification::assertSentTo($admin, \App\Notifications\FormSubmittedNotification::class);
    }

    public function test_submitting_twice_creates_two_independent_submissions(): void
    {
        $admin = User::factory()->create();
        $template = FormTemplate::create([
            'name' => 'Sign Up', 'mode' => FormTemplate::MODE_STANDALONE, 'slug' => 'sign-up',
            'is_active' => true, 'created_by' => $admin->id,
        ]);
        $field = $template->fields()->create(['label' => 'Name', 'field_type' => FormField::TYPE_TEXT]);

        $this->post(route('public.forms.standalone.store', 'sign-up'), ['values' => [$field->key => 'A']]);
        $this->post(route('public.forms.standalone.store', 'sign-up'), ['values' => [$field->key => 'B']]);

        $this->assertEquals(2, FormSubmission::count());
    }
}
