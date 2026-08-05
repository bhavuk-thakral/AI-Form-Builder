<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $otherUser;
    private $form;
    private $version;
    private $submission1;
    private $submission2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $schema = [
            'title' => 'Feedback Sandbox',
            'fields' => [
                ['key' => 'user_name', 'type' => 'text', 'label' => 'Name'],
                ['key' => 'experience_rating', 'type' => 'rating', 'label' => 'Rating']
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Feedback Sandbox',
            'status' => 'active',
            'schema' => $schema,
            'share_token' => 'feedback-share-101',
        ]);

        $this->version = FormVersion::create([
            'form_id' => $this->form->id,
            'version_number' => 1,
            'schema' => $schema,
            'created_by' => $this->user->id,
        ]);

        // Create Submission 1
        $this->submission1 = Submission::create([
            'form_id' => $this->form->id,
            'form_version_id' => $this->version->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser Test',
            'duration_seconds' => 45,
        ]);

        SubmissionAnswer::create([
            'submission_id' => $this->submission1->id,
            'field_key' => 'user_name',
            'answer_value' => 'Alice Dev',
        ]);

        SubmissionAnswer::create([
            'submission_id' => $this->submission1->id,
            'field_key' => 'experience_rating',
            'answer_value' => '5',
        ]);

        // Create Submission 2
        $this->submission2 = Submission::create([
            'form_id' => $this->form->id,
            'form_version_id' => $this->version->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mobile Test',
            'duration_seconds' => 120,
        ]);

        SubmissionAnswer::create([
            'submission_id' => $this->submission2->id,
            'field_key' => 'user_name',
            'answer_value' => 'Bob Builder',
        ]);

        SubmissionAnswer::create([
            'submission_id' => $this->submission2->id,
            'field_key' => 'experience_rating',
            'answer_value' => '3',
        ]);
    }

    /**
     * Test guest users are blocked.
     */
    public function test_guests_cannot_view_submissions()
    {
        $response = $this->get(route('forms.submissions.index', $this->form->id));
        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test form owner can view paginated submissions index.
     */
    public function test_owner_can_view_submissions()
    {
        $response = $this->actingAs($this->user)->get(route('forms.submissions.index', $this->form->id));

        $response->assertStatus(200);
        $response->assertSee('Feedback Sandbox');
        $response->assertSee('Alice Dev');
        $response->assertSee('Bob Builder');
        $response->assertSee('127.0.0.1');
        $response->assertSee('10.0.0.5');
    }

    /**
     * Test filtering list via search.
     */
    public function test_owner_can_filter_submissions_by_search()
    {
        // Search matching Alice
        $response = $this->actingAs($this->user)->get(route('forms.submissions.index', $this->form->id) . '?search=Alice');
        $response->assertStatus(200);
        $response->assertSee('Alice Dev');
        $response->assertDontSee('Bob Builder');

        // Search matching Bob
        $response = $this->actingAs($this->user)->get(route('forms.submissions.index', $this->form->id) . '?search=Bob');
        $response->assertStatus(200);
        $response->assertSee('Bob Builder');
        $response->assertDontSee('Alice Dev');

        // Search matching IP
        $response = $this->actingAs($this->user)->get(route('forms.submissions.index', $this->form->id) . '?search=10.0.0.5');
        $response->assertStatus(200);
        $response->assertSee('Bob Builder');
        $response->assertDontSee('Alice Dev');
    }

    /**
     * Test detail endpoint returns JSON of answers.
     */
    public function test_owner_can_view_submission_detail_json()
    {
        $response = $this->actingAs($this->user)->get(route('submissions.show', $this->submission1->id));

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->submission1->id);
        $response->assertJsonPath('ip_address', '127.0.0.1');
        $response->assertJsonPath('answers.0.label', 'Name');
        $response->assertJsonPath('answers.0.value', 'Alice Dev');
    }

    /**
     * Test other users are blocked from viewing form submissions.
     */
    public function test_other_users_cannot_access_submissions()
    {
        // Block index
        $response = $this->actingAs($this->otherUser)->get(route('forms.submissions.index', $this->form->id));
        $response->assertStatus(403);

        // Block JSON detail
        $response = $this->actingAs($this->otherUser)->get(route('submissions.show', $this->submission1->id));
        $response->assertStatus(403);
    }
}
