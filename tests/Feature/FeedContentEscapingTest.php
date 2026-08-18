<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feed publishers are not a trusted party — anyone can stand up an RSS endpoint. These
 * tests pin the escaping of every feed-controlled value that reaches a Blade template.
 */
class FeedContentEscapingTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    public function test_post_title_is_escaped_in_a_public_issue(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        $post = Post::factory()->create([
            'feed_id' => $feed->id,
            'title' => self::PAYLOAD,
        ]);

        $issue = $user->issues()->create([
            'edition' => 1,
            'issue_date' => now(),
            'posts' => json_encode([$post->id]),
            'posts_excluded' => json_encode([]),
        ]);

        $response = $this->get("/issue/{$issue->id}");

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
        $response->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_feed_description_is_escaped_on_a_public_profile(): void
    {
        $user = User::factory()->create(['username' => 'reader']);
        $feed = Feed::factory()->create(['description' => self::PAYLOAD]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        $response = $this->get('/@reader');

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
    }

    public function test_feed_description_is_escaped_on_the_home_page(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create(['description' => self::PAYLOAD]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
    }

    public function test_javascript_scheme_audio_url_is_dropped(): void
    {
        $post = Post::factory()->make(['audio_url' => "javascript:alert('xss')"]);

        $this->assertNull($post->safe_audio_url);
    }

    public function test_data_scheme_audio_url_is_dropped(): void
    {
        $post = Post::factory()->make(['audio_url' => 'data:text/html,<script>alert(1)</script>']);

        $this->assertNull($post->safe_audio_url);
    }

    public function test_http_audio_url_is_preserved(): void
    {
        $url = 'https://example.com/episode.mp3';
        $post = Post::factory()->make(['audio_url' => $url]);

        $this->assertSame($url, $post->safe_audio_url);
    }

    public function test_null_audio_url_stays_null(): void
    {
        $post = Post::factory()->make(['audio_url' => null]);

        $this->assertNull($post->safe_audio_url);
    }
}
