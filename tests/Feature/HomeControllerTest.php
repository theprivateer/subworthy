<?php

namespace Tests\Feature;

use App\Models\Feed;
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
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/home')
            ->assertViewIs('home')
            ->assertViewHas('subscriptions', fn ($s) => $s->contains($subscription));
    }

    public function test_video_icon_is_only_shown_for_video_feeds(): void
    {
        $user = User::factory()->create();
        $videoFeed = Feed::factory()->create([
            'title' => 'Video Channel',
            'url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UC123',
        ]);
        $regularFeed = Feed::factory()->create([
            'title' => 'Written Feed',
            'url' => 'https://example.com/feed.xml',
        ]);

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $videoFeed->id]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $regularFeed->id]);

        $response = $this->actingAs($user)->get('/home');

        $response
            ->assertOk()
            ->assertSee('Video Channel')
            ->assertSee('Written Feed');

        $this->assertSame(1, substr_count($response->getContent(), '>Video feed</span>'));
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
