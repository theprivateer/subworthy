<?php

namespace Tests\Feature;

use App\Ai\Agents\PostSummariser;
use App\Jobs\SummarisePost;
use App\Models\Feed;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\TestCase;

class BackfillPostSummariesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PostSummariser::fake();
        Queue::fake();

        config(['ai.providers.openai.key' => 'test-key']);
    }

    // -------------------------------------------------------------------------
    // Default behaviour (no --force)
    // -------------------------------------------------------------------------

    public function test_only_posts_missing_summary_or_themes_are_dispatched(): void
    {
        $needsSummary = Post::factory()->create(['raw' => str_repeat('word ', 60), 'summary' => null,       'themes' => ['technology']]);
        $needsThemes = Post::factory()->create(['raw' => str_repeat('word ', 60), 'summary' => 'Existing.', 'themes' => null]);
        $complete = Post::factory()->create(['raw' => str_repeat('word ', 60), 'summary' => 'Existing.', 'themes' => ['technology']]);

        $this->artisan('posts:backfill-summaries', ['--feed' => 'all', '--limit' => ''])
            ->assertSuccessful();

        Queue::assertPushed(SummarisePost::class, 2);
        Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $needsSummary));
        Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $needsThemes));
        Queue::assertNotPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $complete));
    }

    public function test_posts_with_no_content_are_skipped(): void
    {
        Post::factory()->create(['raw' => null, 'fetched_raw' => null, 'summary' => null, 'themes' => null]);

        $this->artisan('posts:backfill-summaries', ['--feed' => 'all', '--limit' => ''])
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // --force flag
    // -------------------------------------------------------------------------

    public function test_force_dispatches_all_posts_regardless_of_existing_summary_and_themes(): void
    {
        $complete = Post::factory()->create(['raw' => str_repeat('word ', 60), 'summary' => 'Existing.', 'themes' => ['technology']]);
        $missing = Post::factory()->create(['raw' => str_repeat('word ', 60), 'summary' => null,         'themes' => null]);

        $this->artisan('posts:backfill-summaries', ['--force' => true, '--feed' => 'all', '--limit' => ''])
            ->assertSuccessful();

        Queue::assertPushed(SummarisePost::class, 2);
        Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $complete));
        Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $missing));
    }

    public function test_force_still_skips_posts_with_no_content(): void
    {
        Post::factory()->create(['raw' => null, 'fetched_raw' => null, 'summary' => 'Existing.', 'themes' => ['technology']]);

        $this->artisan('posts:backfill-summaries', ['--force' => true, '--feed' => 'all', '--limit' => ''])
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Feed filtering
    // -------------------------------------------------------------------------

    public function test_scopes_to_a_specific_feed_when_requested(): void
    {
        $feed = Feed::factory()->create();
        $other = Feed::factory()->create();

        $inFeed = Post::factory()->for($feed)->create(['raw' => str_repeat('word ', 60), 'summary' => null, 'themes' => null]);
        $outFeed = Post::factory()->for($other)->create(['raw' => str_repeat('word ', 60), 'summary' => null, 'themes' => null]);

        $this->artisan('posts:backfill-summaries', ['--feed' => $feed->id, '--limit' => ''])
            ->assertSuccessful();

        Queue::assertPushed(SummarisePost::class, 1);
        Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $inFeed));
        Queue::assertNotPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $outFeed));
    }

    // -------------------------------------------------------------------------
    // Chunking
    // -------------------------------------------------------------------------

    public function test_every_post_is_dispatched_exactly_once_across_chunks(): void
    {
        // Must exceed the command's chunk size of 100 to cross a chunk boundary. published_at
        // deliberately runs opposite to id: chunkById pages on id, so any competing orderBy
        // makes it skip posts and re-dispatch others.
        $posts = collect(range(1, 150))->map(fn ($i) => Post::factory()->create([
            'raw' => str_repeat('word ', 60),
            'summary' => null,
            'themes' => null,
            'published_at' => now()->subDays($i),
        ]));

        $this->artisan('posts:backfill-summaries', ['--feed' => 'all', '--limit' => ''])
            ->assertSuccessful();

        Queue::assertPushed(SummarisePost::class, 150);

        foreach ($posts as $post) {
            Queue::assertPushed(SummarisePost::class, fn ($job) => $this->jobTargets($job, $post));
        }
    }

    public function test_limit_caps_the_number_of_dispatched_jobs(): void
    {
        Post::factory()->count(10)->create(['raw' => str_repeat('word ', 60), 'summary' => null, 'themes' => null]);

        $this->artisan('posts:backfill-summaries', ['--feed' => 'all', '--limit' => '4'])
            ->assertSuccessful();

        Queue::assertPushed(SummarisePost::class, 4);
    }

    // -------------------------------------------------------------------------
    // No API key
    // -------------------------------------------------------------------------

    public function test_fails_when_no_api_key_is_configured(): void
    {
        config(['ai.providers.openai.key' => null]);

        $this->artisan('posts:backfill-summaries', ['--feed' => 'all', '--limit' => ''])
            ->assertFailed();

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function jobTargets(SummarisePost $job, Post $post): bool
    {
        $ref = new ReflectionProperty($job, 'postId');

        return $ref->getValue($job) === $post->id;
    }
}
