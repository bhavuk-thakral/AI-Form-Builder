<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTemplateTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test guest users are blocked.
     */
    public function test_guests_cannot_create_from_template()
    {
        $response = $this->post(route('forms.template'), [
            'template_key' => 'contact'
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test invalid template key.
     */
    public function test_invalid_template_key_fails_validation()
    {
        $response = $this->actingAs($this->user)->post(route('forms.template'), [
            'template_key' => 'invalid-preset'
        ]);

        $response->assertSessionHasErrors('template_key');
    }

    /**
     * Test creating contact support template.
     */
    public function test_user_can_create_contact_form_template()
    {
        $response = $this->actingAs($this->user)->post(route('forms.template'), [
            'template_key' => 'contact'
        ]);

        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $this->assertEquals('Contact Us Support Form', $form->title);
        $this->assertCount(3, $form->schema['fields']);

        // Check version
        $version = FormVersion::first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'form_id' => $form->id,
            'action' => 'created',
            'description' => 'Created form from template: Contact Us Support Form.',
        ]);
    }

    /**
     * Test creating feedback template.
     */
    public function test_user_can_create_feedback_form_template()
    {
        $response = $this->actingAs($this->user)->post(route('forms.template'), [
            'template_key' => 'feedback'
        ]);

        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $this->assertEquals('Customer Feedback Survey', $form->title);
        $this->assertCount(3, $form->schema['fields']);
        $this->assertEquals('rating', $form->schema['fields'][1]['type']);
    }

    /**
     * Test creating event registration template.
     */
    public function test_user_can_create_event_form_template()
    {
        $response = $this->actingAs($this->user)->post(route('forms.template'), [
            'template_key' => 'event'
        ]);

        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $this->assertEquals('Annual Summit Registration Form', $form->title);
        $this->assertCount(4, $form->schema['fields']);
        $this->assertEquals('dropdown', $form->schema['fields'][2]['type']);
        $this->assertEquals('checkbox', $form->schema['fields'][3]['type']);
    }
}
