<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalLogicTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Create form with a conditional field schema
        $schema = [
            'title' => 'Survey Form',
            'fields' => [
                [
                    'id' => 'f_1',
                    'key' => 'marital_status',
                    'type' => 'dropdown',
                    'label' => 'Marital Status',
                    'options' => ['Single', 'Married', 'Divorced']
                ],
                [
                    'id' => 'f_2',
                    'key' => 'spouse_name',
                    'type' => 'text',
                    'label' => 'Spouse Name',
                    'condition_field' => 'marital_status',
                    'condition_operator' => 'equals',
                    'condition_value' => 'Married'
                ]
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Survey Form',
            'status' => 'active',
            'schema' => $schema,
            'share_token' => 'conditional-slug-16',
        ]);
    }

    /**
     * Test saving schema with conditional rules.
     */
    public function test_user_can_save_conditional_logic_rules()
    {
        $newSchema = [
            'title' => 'Updated Survey Form',
            'fields' => [
                [
                    'id' => 'f_1',
                    'key' => 'marital_status',
                    'type' => 'dropdown',
                    'label' => 'Marital Status',
                    'options' => ['Single', 'Married', 'Divorced']
                ],
                [
                    'id' => 'f_2',
                    'key' => 'spouse_name',
                    'type' => 'text',
                    'label' => 'Spouse Name',
                    'condition_field' => 'marital_status',
                    'condition_operator' => 'not_equals',
                    'condition_value' => 'Single'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->patch(route('forms.update', $this->form->id), [
            'title' => 'Updated Survey Form',
            'status' => 'active',
            'schema' => json_encode($newSchema)
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->form->refresh();

        // Verify rules are stored correctly
        $fields = $this->form->schema['fields'];
        $this->assertEquals('marital_status', $fields[1]['condition_field']);
        $this->assertEquals('not_equals', $fields[1]['condition_operator']);
        $this->assertEquals('Single', $fields[1]['condition_value']);
    }

    /**
     * Test public form container renders with conditional attributes.
     */
    public function test_public_form_renders_with_conditional_attributes()
    {
        $response = $this->get(route('forms.public.show', $this->form->share_token));

        $response->assertStatus(200);
        $response->assertSee('data-condition-field="marital_status"', false);
        $response->assertSee('data-condition-operator="equals"', false);
        $response->assertSee('data-condition-value="Married"', false);
    }
}
