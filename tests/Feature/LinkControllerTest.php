<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Post}
     */
    private function subscribedUserAndPost(array $postAttributes = []): array
    {
        $user = User::factory()->create(['last_interaction_at' => null]);
        $feed = Feed::factory()->create();

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        $post = Post::factory()->create($postAttributes + ['feed_id' => $feed->id]);

        return [$user, $post];
    }

    public function test_show_logs_interaction_on_the_user(): void
    {
        [$user, $post] = $this->subscribedUserAndPost();

        $this->get("/link/{$user->id}/{$post->id}");

        $this->assertNotNull($user->fresh()->last_interaction_at);
    }

    public function test_show_redirects_to_the_post_url(): void
    {
        [$user, $post] = $this->subscribedUserAndPost(['url' => 'https://example.com/article']);

        $this->get("/link/{$user->id}/{$post->id}")
            ->assertRedirect('https://example.com/article');
    }

    // -------------------------------------------------------------------------
    // relationship check
    // -------------------------------------------------------------------------

    /**
     * Without this, anyone could pair any user id with any post id — minting a tracked
     * redirect on this domain and bumping a stranger's activity timestamp at will.
     */
    public function test_it_404s_when_the_user_does_not_subscribe_to_the_posts_feed(): void
    {
        [, $post] = $this->subscribedUserAndPost();

        $stranger = User::factory()->create(['last_interaction_at' => null]);

        $this->get("/link/{$stranger->id}/{$post->id}")->assertNotFound();

        $this->assertNull($stranger->fresh()->last_interaction_at);
    }

    public function test_it_404s_after_the_user_unsubscribes(): void
    {
        [$user, $post] = $this->subscribedUserAndPost();

        Subscription::where('user_id', $user->id)->delete();

        $this->get("/link/{$user->id}/{$post->id}")->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // destination scheme
    // -------------------------------------------------------------------------

    public function test_it_refuses_to_redirect_to_a_javascript_url(): void
    {
        [$user, $post] = $this->subscribedUserAndPost(['url' => "javascript:alert('xss')"]);

        $this->get("/link/{$user->id}/{$post->id}")->assertNotFound();
    }

    public function test_it_refuses_to_redirect_to_a_data_url(): void
    {
        [$user, $post] = $this->subscribedUserAndPost([
            'url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $this->get("/link/{$user->id}/{$post->id}")->assertNotFound();
    }

    public function test_it_allows_a_plain_http_url(): void
    {
        [$user, $post] = $this->subscribedUserAndPost(['url' => 'http://example.com/article']);

        $this->get("/link/{$user->id}/{$post->id}")
            ->assertRedirect('http://example.com/article');
    }
}
