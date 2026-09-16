<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Group;
use App\Models\User;
use App\Services\FormNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_group_members_via_database_only(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $group = Group::create(['name' => 'Retention', 'is_active' => true]);
        $member = User::factory()->create();
        $group->members()->attach($member->id);

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'notify_group_id' => $group->id,
            'created_by' => $creator->id,
        ]);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        FormNotifier::notifySubmitted($submission);

        Notification::assertSentTo($member, \App\Notifications\FormSubmittedNotification::class);

        $notification = new \App\Notifications\FormSubmittedNotification($submission);
        $this->assertEquals(['database'], $notification->via($member));
    }
}
