<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coam_equipment_link_generation_and_public_signing_still_work(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('admin.agreements.store'), [
            'type' => 'coam_equipment',
            'account_id' => 'ACC123',
            'form_date' => now()->toDateString(),
            'expires_in' => '7',
        ])->assertRedirect();

        $agreement = Agreement::firstOrFail();
        $this->assertEquals('coam_equipment', $agreement->type);

        $this->get(route('public.agreements.show', $agreement->uuid))->assertOk();
    }
}
