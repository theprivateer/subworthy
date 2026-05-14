<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_verified_user_sees_home_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/home')->assertOk();
    }

    public function test_home_page_passes_subscriptions_to_view(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/home')
            ->assertViewIs('home')
            ->assertViewHas('subscriptions', fn ($s) => $s->contains($subscription));
    }

    public function test_home_page_passes_last_7_issues_to_view(): void
    {
        $user = User::factory()->create();

        // Create 9 issues — only the 7 most recent should appear.
        Issue::factory()->count(9)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/home')
            ->assertViewHas('issues', fn ($issues) => $issues->count() === 7);
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_unverified_user_is_redirected_to_email_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect('/verify-email');
    }
}
