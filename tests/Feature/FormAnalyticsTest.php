<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private $owner;
    private $otherUser;
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $schema = [
            'title' => 'Feedback Form',
            'fields' => [
                [
                    'key' => 'satisfaction',
                    'type' => 'rating',
                    'label' => 'Satisfaction',
                ]
            ]
        ];

        $this->form = Form::create([
            'user_id' => $this->owner->id,
            'title' => 'Feedback Form',
            'status' => 'active',
            'schema' => $schema,
            'views_count' => 10,
            'share_token' => 'analytics-slug-18',
        ]);

        // Create 2 submissions
        $sub1 = Submission::create([
            'form_id' => $this->form->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'duration_seconds' => 30,
        ]);
        SubmissionAnswer::create([
            'submission_id' => $sub1->id,
            'field_key' => 'satisfaction',
            'answer_value' => '5',
        ]);

        $sub2 = Submission::create([
            'form_id' => $this->form->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/100',
            'duration_seconds' => 40,
        ]);
        SubmissionAnswer::create([
            'submission_id' => $sub2->id,
            'field_key' => 'satisfaction',
            'answer_value' => '4',
        ]);
    }

    /**
     * Test guest cannot view analytics.
     */
    public function test_guests_cannot_view_analytics()
    {
        $response = $this->get(route('forms.analytics', $this->form->id));

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    /**
     * Test other users cannot view analytics.
     */
    public function test_other_users_cannot_view_analytics()
    {
        $response = $this->actingAs($this->otherUser)->get(route('forms.analytics', $this->form->id));

        $response->assertStatus(403);
    }

    /**
     * Test owner can view analytics metrics and view counts.
     */
    public function test_owner_can_view_analytics_metrics()
    {
        $response = $this->actingAs($this->owner)->get(route('forms.analytics', $this->form->id));

        $response->assertStatus(200);
        $response->assertViewHas('views', 10);
        $response->assertViewHas('submissionsCount', 2);
        $response->assertViewHas('conversionRate', 20.0); // 2/10 * 100
        $response->assertViewHas('avgDuration', 35); // (30+40)/2
        $response->assertSee('Feedback Form Analytics');
        $response->assertSee('responses');
    }
}
