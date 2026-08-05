<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormVersioningTest extends TestCase
{
    use RefreshDatabase;

    private $owner;
    private $otherUser;
    private $form;
    private $version1;
    private $version2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $schema1 = [
            'title' => 'Job Application v1',
            'fields' => [
                ['key' => 'fullname', 'type' => 'text', 'label' => 'Name']
            ]
        ];

        $schema2 = [
            'title' => 'Job Application v2',
            'fields' => [
                ['key' => 'fullname', 'type' => 'text', 'label' => 'Name'],
                ['key' => 'email', 'type' => 'email', 'label' => 'Email']
            ]
        ];

        // Create form pointing to schema2
        $this->form = Form::create([
            'user_id' => $this->owner->id,
            'title' => 'Job Application v2',
            'description' => 'Revised details',
            'status' => 'draft',
            'schema' => $schema2,
            'share_token' => 'rollback-test-slug',
        ]);

        // Version 1
        $this->version1 = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 1,
            'schema' => $schema1,
            'created_by' => $this->owner->id,
        ]);

        // Version 2
        $this->version2 = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 2,
            'schema' => $schema2,
            'created_by' => $this->owner->id,
        ]);
    }

    /**
     * Test guest cannot restore version.
     */
    public function test_guests_cannot_restore_version()
    {
        $response = $this->post(route('forms.restore-version', [$this->form->id, $this->version1->id]));

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test other users cannot restore version.
     */
    public function test_other_users_cannot_restore_version()
    {
        $response = $this->actingAs($this->otherUser)->post(
            route('forms.restore-version', [$this->form->id, $this->version1->id])
        );

        $response->assertStatus(403);
    }

    /**
     * Test owner can restore version, schema is rolled back, and version 3 is generated.
     */
    public function test_owner_can_restore_version_creates_checkpoint()
    {
        $response = $this->actingAs($this->owner)->post(
            route('forms.restore-version', [$this->form->id, $this->version1->id])
        );

        $response->assertRedirect(route('forms.edit', $this->form->id));
        $response->assertSessionHas('toast_success');

        // Refresh Form
        $this->form->refresh();

        // Schema should match version 1 (only 1 field)
        $this->assertEquals('Job Application v1', $this->form->title);
        $this->assertCount(1, $this->form->schema['fields']);
        $this->assertEquals('fullname', $this->form->schema['fields'][0]['key']);

        // Check new version (Version 3) is generated as checkpoint
        $latestVersion = FormVersion::orderBy('version_number', 'desc')->first();
        $this->assertNotNull($latestVersion);
        $this->assertEquals(3, $latestVersion->version_number);
        $this->assertEquals($this->form->id, $latestVersion->form_id);
        $this->assertEquals($this->version1->schema, $latestVersion->schema);

        // Check Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'form_id' => $this->form->id,
            'action' => 'restored',
            'description' => 'Restored form layout back to Version #1.',
        ]);
    }
}
