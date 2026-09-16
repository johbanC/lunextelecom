<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTemplateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_template_can_have_ordered_fields_with_options(): void
    {
        $user = User::factory()->create();

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'requires_signature' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $second = $template->fields()->create([
            'label' => 'Market',
            'field_type' => FormField::TYPE_SELECT,
            'sort_order' => 1,
        ]);
        $first = $template->fields()->create([
            'label' => 'Full Name',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $second->options()->create(['value' => 'Latino Community', 'sort_order' => 0]);

        $this->assertTrue($first->is_required);
        $this->assertEquals('full_name', $first->key);
        $this->assertFalse($second->isMultiValue());
        $this->assertEquals(['Full Name', 'Market'], $template->fields()->pluck('label')->all());
        $this->assertEquals('Latino Community', $second->options->first()->value);
        $this->assertTrue($second->options->first()->field->is($second));
    }
}
