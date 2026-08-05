<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test form creation logic.
     */
    public function test_user_can_create_a_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/forms', [
            'title' => 'Job Application Form',
            'description' => 'Apply for engineering roles.',
        ]);

        // Assert redirect to the canvas builder edit route
        $form = Form::first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('forms.edit', $form->id));
        $response->assertSessionHas('toast_success');

        // Check Form Database Fields
        $this->assertEquals('Job Application Form', $form->title);
        $this->assertEquals('Apply for engineering roles.', $form->description);
        $this->assertEquals('draft', $form->status);
        $this->assertNotEmpty($form->share_token);

        // Check Version 1 Created
        $version = FormVersion::where('form_id', $form->id)->first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals($form->schema, $version->schema);

        // Check Activity Log
        $log = ActivityLog::where('form_id', $form->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('created', $log->action);
    }

    /**
     * Test updating form general settings.
     */
    public function test_user_can_update_form_settings()
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old Desc',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'old-token',
        ]);

        $response = $this->actingAs($user)->patch("/forms/{$form->id}", [
            'title' => 'New Title',
            'description' => 'New Desc',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('toast_success');

        $form->refresh();
        $this->assertEquals('New Title', $form->title);
        $this->assertEquals('New Desc', $form->description);
        $this->assertEquals('active', $form->status);

        // Check Activity Log
        $log = ActivityLog::where('form_id', $form->id)->where('action', 'updated')->first();
        $this->assertNotNull($log);
    }

    /**
     * Test user cannot update or delete forms belonging to another user.
     */
    public function test_user_cannot_modify_other_users_forms()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $form = Form::create([
            'user_id' => $user1->id,
            'title' => 'User 1 Form',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'token-u1',
        ]);

        // Attempting edit view access
        $response = $this->actingAs($user2)->get("/forms/{$form->id}/edit");
        $response->assertStatus(403);

        // Attempting update settings
        $response = $this->actingAs($user2)->patch("/forms/{$form->id}", [
            'title' => 'Hacked Title',
            'status' => 'active',
        ]);
        $response->assertStatus(403);

        // Attempting delete
        $response = $this->actingAs($user2)->delete("/forms/{$form->id}");
        $response->assertStatus(403);

        $this->assertDatabaseHas('forms', ['id' => $form->id, 'title' => 'User 1 Form']);
    }

    /**
     * Test deleting form.
     */
    public function test_user_can_delete_a_form()
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'To Be Deleted',
            'status' => 'draft',
            'schema' => ['fields' => []],
            'share_token' => 'delete-token',
        ]);

        $response = $this->actingAs($user)->delete("/forms/{$form->id}");

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('toast_success');

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);

        // Log entry remains (form_id set null)
        $log = ActivityLog::where('user_id', $user->id)->where('action', 'deleted')->first();
        $this->assertNotNull($log);
    }
}
