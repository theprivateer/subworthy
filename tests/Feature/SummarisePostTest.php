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
    // No AI provider configured
    // -------------------------------------------------------------------------

    public function test_job_skips_silently_when_no_api_key_is_configured(): void
    {
        PostSummariser::fake();

        config(['ai.providers.openai.key' => null]);

        $post = Post::factory()->create(['raw' => str_repeat('word ', 60)]);

        (new SummarisePost($post->id))->handle();

        PostSummariser::assertNeverPrompted();
        $this->assertNull($post->fresh()->summary);
        $this->assertNull($post->fresh()->themes);
    }

    // -------------------------------------------------------------------------
    // Word threshold
    // -------------------------------------------------------------------------

    public function test_post_below_word_threshold_gets_themes_but_not_summary(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 49),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        PostSummariser::assertPrompted(fn ($p) => true);
        $this->assertNull($post->fresh()->summary);
        $this->assertEquals(['technology'], $post->fresh()->themes);
    }

    public function test_post_at_word_threshold_is_summarised(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 50),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('Generated summary.', $post->fresh()->summary);
    }

    // -------------------------------------------------------------------------
    // Content source preference
    // -------------------------------------------------------------------------

    public function test_raw_content_is_used_when_fetched_raw_is_absent(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Summary from raw.',
            'themes' => ['culture'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 60),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('Summary from raw.', $post->fresh()->summary);
    }

    public function test_fetched_raw_is_preferred_over_raw_for_summarisation(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Summary from fetched.',
            'themes' => ['science'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('short ', 5),
            'fetched_raw' => str_repeat('word ', 60),
        ]);

        (new SummarisePost($post->id))->handle();

        // fetched_raw has enough words; raw alone would be skipped
        $this->assertEquals('Summary from fetched.', $post->fresh()->summary);
    }

    public function test_post_with_no_content_is_skipped_entirely(): void
    {
        PostSummariser::fake();

        $post = Post::factory()->create([
            'raw' => null,
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        PostSummariser::assertNeverPrompted();
        $this->assertNull($post->fresh()->summary);
        $this->assertNull($post->fresh()->themes);
    }

    // -------------------------------------------------------------------------
    // HTML stripping
    // -------------------------------------------------------------------------

    public function test_html_tags_are_stripped_before_word_count(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        // 49 words of text wrapped in HTML — tags must not count towards the summary threshold
        $words = str_repeat('word ', 49);
        $post = Post::factory()->create([
            'raw' => "<div><p><strong>{$words}</strong></p></div>",
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        // AI is still called (themes are always generated), but summary is withheld
        PostSummariser::assertPrompted(fn ($p) => true);
        $this->assertNull($post->fresh()->summary);
        $this->assertNotNull($post->fresh()->themes);
    }

    // -------------------------------------------------------------------------
    // Themes persistence
    // -------------------------------------------------------------------------

    public function test_themes_are_persisted_on_the_post(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology', 'business'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 60),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals(['technology', 'business'], $post->fresh()->themes);
    }

    // -------------------------------------------------------------------------
    // Content length cap
    // -------------------------------------------------------------------------

    public function test_content_sent_to_the_provider_is_capped(): void
    {
        config(['feeds.summarise_max_characters' => 500]);

        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 5000),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        // 500 characters of content, plus the "Summarise this article:" preamble
        PostSummariser::assertPrompted(fn ($prompt) => strlen($prompt->prompt) < 600);
    }

    public function test_truncation_does_not_drop_a_long_post_below_the_word_threshold(): void
    {
        // Cap well below the threshold: the word count must come from the full content,
        // not from the truncated prompt, or long posts would lose their summary.
        config(['feeds.summarise_max_characters' => 20]);

        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('word ', 200),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('Generated summary.', $post->fresh()->summary);
    }

    // -------------------------------------------------------------------------
    // Word counting beyond ASCII
    // -------------------------------------------------------------------------

    public function test_cyrillic_content_above_the_threshold_is_summarised(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Сводка.',
            'themes' => ['culture'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('слово ', 60),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('Сводка.', $post->fresh()->summary);
    }

    public function test_accented_latin_content_above_the_threshold_is_summarised(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Résumé.',
            'themes' => ['culture'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('éphémère ', 60),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('Résumé.', $post->fresh()->summary);
    }

    public function test_cjk_content_is_counted_per_character(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => '概要。',
            'themes' => ['technology'],
        ]);

        // 60 characters with no whitespace — whitespace tokenising would score this as 1 word
        $post = Post::factory()->create([
            'raw' => str_repeat('日本語', 20),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertEquals('概要。', $post->fresh()->summary);
    }

    public function test_short_non_ascii_content_still_falls_below_the_threshold(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Сводка.',
            'themes' => ['culture'],
        ]);

        $post = Post::factory()->create([
            'raw' => str_repeat('слово ', 10),
            'fetched_raw' => null,
        ]);

        (new SummarisePost($post->id))->handle();

        $this->assertNull($post->fresh()->summary);
        $this->assertEquals(['culture'], $post->fresh()->themes);
    }

    // -------------------------------------------------------------------------
    // Missing posts
    // -------------------------------------------------------------------------

    public function test_job_skips_silently_when_the_post_no_longer_exists(): void
    {
        PostSummariser::fake();

        $post = Post::factory()->create(['raw' => str_repeat('word ', 60)]);
        $postId = $post->id;

        // Posts are pruned after a month, so a queued job can outlive its post
        $post->delete();

        (new SummarisePost($postId))->handle();

        PostSummariser::assertNeverPrompted();
    }

    public function test_job_reads_the_post_as_it_stands_when_it_runs(): void
    {
        PostSummariser::fake(fn () => [
            'summary' => 'Generated summary.',
            'themes' => ['technology'],
        ]);

        $post = Post::factory()->create(['raw' => null, 'fetched_raw' => null]);

        $job = new SummarisePost($post->id);

        // Simulates FetchFullPost writing enriched content after the job was queued
        $post->update(['fetched_raw' => str_repeat('word ', 60)]);

        $job->handle();

        $this->assertEquals('Generated summary.', $post->fresh()->summary);
    }

    // -------------------------------------------------------------------------
    // Failure handling
    // -------------------------------------------------------------------------

    public function test_failed_logs_post_id_and_error_message(): void
    {
        Log::spy();

        $post = Post::factory()->create();
        $exception = new \Exception('API timeout');

        (new SummarisePost($post->id))->failed($exception);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message, $context) => $message === 'SummarisePost failed'
                && $context['post_id'] === $post->id
                && $context['error'] === 'API timeout'
            );
    }
}
