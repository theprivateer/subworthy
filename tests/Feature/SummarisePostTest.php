<?php

namespace Tests\Feature;

use App\Ai\Agents\PostSummariser;
use App\Jobs\SummarisePost;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SummarisePostTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Word threshold
    // -------------------------------------------------------------------------

    public function test_post_below_word_threshold_is_not_summarised(): void
    {
        PostSummariser::fake();

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 49),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post))->handle();

        PostSummariser::assertNeverPrompted();
        $this->assertNull($post->fresh()->summary);
    }

    public function test_post_at_word_threshold_is_summarised(): void
    {
        PostSummariser::fake(['Generated summary.']);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 50),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post))->handle();

        $this->assertEquals('Generated summary.', $post->fresh()->summary);
    }

    // -------------------------------------------------------------------------
    // Content source preference
    // -------------------------------------------------------------------------

    public function test_raw_content_is_used_when_fetched_raw_is_absent(): void
    {
        PostSummariser::fake(['Summary from raw.']);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 60),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post))->handle();

        $this->assertEquals('Summary from raw.', $post->fresh()->summary);
    }

    public function test_fetched_raw_is_preferred_over_raw_for_summarisation(): void
    {
        PostSummariser::fake(['Summary from fetched.']);

        $post = Post::factory()->create([
            'raw' => str_repeat('short ', 5),
            'fetched_raw' => str_repeat('word ', 60),
        ]);

        (new SummarisePost($post))->handle();

        // fetched_raw has enough words; raw alone would be skipped
        $this->assertEquals('Summary from fetched.', $post->fresh()->summary);
    }

    public function test_post_with_null_content_is_skipped(): void
    {
        PostSummariser::fake();

        $post = Post::factory()->create([
            'raw' => null,
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post))->handle();

        PostSummariser::assertNeverPrompted();
        $this->assertNull($post->fresh()->summary);
    }

    // -------------------------------------------------------------------------
    // HTML stripping
    // -------------------------------------------------------------------------

    public function test_html_tags_are_stripped_before_word_count(): void
    {
        PostSummariser::fake();

        // 49 words of text wrapped in HTML — tags must not count towards the threshold
        $words = str_repeat('word ', 49);
        $post = Post::factory()->create([
            'raw' => "<div><p><strong>{$words}</strong></p></div>",
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post))->handle();

        PostSummariser::assertNeverPrompted();
    }

    // -------------------------------------------------------------------------
    // Failure handling
    // -------------------------------------------------------------------------

    public function test_failed_logs_post_id_and_error_message(): void
    {
        Log::spy();

        $post = Post::factory()->create();
        $exception = new \Exception('API timeout');

        (new SummarisePost($post))->failed($exception);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message, $context) =>
                $message === 'SummarisePost failed'
                && $context['post_id'] === $post->id
                && $context['error'] === 'API timeout'
            );
    }
}
