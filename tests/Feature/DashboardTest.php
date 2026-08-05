<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the dashboard.
     */
    public function test_guests_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that authenticated users can access the dashboard.
     */
    public function test_authenticated_users_can_access_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Overview');
        $response->assertSee('TOTAL FORMS');
        $response->assertSee('SUBMISSIONS');
        $response->assertSee('AI GENERATIONS');
        $response->assertSee('CONVERSION RATE');
        $response->assertSee('Internship Application Form');
        $response->assertSee('Customer Feedback Survey');
        $response->assertSee($user->name);
    }
}
