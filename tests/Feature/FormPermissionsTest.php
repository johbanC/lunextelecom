<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agente_can_manage_submissions_but_not_templates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $this->assertTrue($agente->can('viewAny', FormSubmission::class));
        $this->assertTrue($agente->can('create', FormSubmission::class));
        $this->assertFalse($agente->can('viewAny', FormTemplate::class));
    }

    public function test_admin_can_manage_everything(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->assertTrue($admin->can('viewAny', FormTemplate::class));
        $this->assertTrue($admin->can('create', FormTemplate::class));
        $this->assertTrue($admin->can('manage', FormSubmission::class));
    }
}
