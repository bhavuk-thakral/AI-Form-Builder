<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test builder screen contains visual toolbox buttons.
     */
    public function test_builder_canvas_page_loads()
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Canvas Sandbox Form',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'sandbox-123',
        ]);

        $response = $this->actingAs($user)->get("/forms/{$form->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Canvas Builder');
        $response->assertSee('Raw JSON Editor');
        $response->assertSee('Field Toolbox');
        $response->assertSee('Section Heading');
        $response->assertSee('Short Text');
        $response->assertSee('Star Rating');
    }

    /**
     * Test saving form layouts and field configurations.
     */
    public function test_user_can_save_form_field_schema()
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Form Schema Test',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'schema-token-12',
        ]);

        $newSchema = [
            'title' => 'Updated Form Title',
            'description' => 'Updated Form Description',
            'fields' => [
                [
                    'key' => 'full_name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'placeholder' => 'Enter your name',
                    'required' => true,
                    'validations' => ['min:3', 'max:50'],
                ],
                [
                    'key' => 'job_role',
                    'type' => 'dropdown',
                    'label' => 'Preferred Role',
                    'required' => false,
                    'options' => ['Developer', 'Designer'],
                ]
            ]
        ];

        // Send request with schema serialized as JSON string (matching frontend submit behavior)
        $response = $this->actingAs($user)->patch("/forms/{$form->id}", [
            'title' => 'Updated Form Title',
            'description' => 'Updated Form Description',
            'status' => 'active',
            'schema' => json_stringify($newSchema),
        ]);

        $response->assertRedirect(route('dashboard'));
        
        $form->refresh();
        $this->assertEquals('Updated Form Title', $form->title);
        $this->assertEquals('Updated Form Description', $form->description);
        $this->assertEquals('active', $form->status);
        $this->assertIsArray($form->schema);
        $this->assertCount(2, $form->schema['fields']);
        
        // Assert field properties
        $this->assertEquals('full_name', $form->schema['fields'][0]['key']);
        $this->assertEquals('text', $form->schema['fields'][0]['type']);
        $this->assertTrue($form->schema['fields'][0]['required']);
        $this->assertEquals(['min:3', 'max:50'], $form->schema['fields'][0]['validations']);
        
        $this->assertEquals('job_role', $form->schema['fields'][1]['key']);
        $this->assertEquals(['Developer', 'Designer'], $form->schema['fields'][1]['options']);
    }

    /**
     * Test generating Laravel validation rules dynamically from JSON Schema.
     */
    public function test_dynamic_validation_rules_generation()
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Form Rules Validation Test',
            'status' => 'draft',
            'schema' => [
                'fields' => [
                    [
                        'key' => 'email_address',
                        'type' => 'email',
                        'label' => 'Your Email',
                        'required' => true,
                    ],
                    [
                        'key' => 'age',
                        'type' => 'number',
                        'label' => 'Your Age',
                        'required' => false,
                        'validations' => ['min:18'],
                    ],
                    [
                        'key' => 'colors',
                        'type' => 'checkbox',
                        'label' => 'Favorite Colors',
                        'required' => true,
                        'options' => ['Red', 'Green', 'Blue'],
                    ]
                ]
            ],
            'share_token' => 'validation-rules-test-12',
        ]);

        $service = app(\App\Services\FormBuilderService::class);
        $rules = $service->generateValidationRules($form);

        // Assert validation rules structure
        $this->assertArrayHasKey('email_address', $rules);
        $this->assertContains('required', $rules['email_address']);
        $this->assertContains('email', $rules['email_address']);

        $this->assertArrayHasKey('age', $rules);
        $this->assertContains('nullable', $rules['age']);
        $this->assertContains('numeric', $rules['age']);
        $this->assertContains('min:18', $rules['age']);

        $this->assertArrayHasKey('colors', $rules);
        $this->assertEquals(['required', 'array'], $rules['colors']);
        $this->assertArrayHasKey('colors.*', $rules);
    }
}

// Helper mock to avoid PHP compilation error if json_stringify isn't helpered
if (!function_exists('json_stringify')) {
    function json_stringify($value) {
        return json_encode($value);
    }
}
