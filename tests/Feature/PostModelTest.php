<?php

namespace Tests\Feature;

use App\Models\ArchivedPost;
use App\Models\Feed;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // getBodyAttribute()
    // -------------------------------------------------------------------------

    public function test_body_uses_fetched_raw_when_present(): void
    {
        $post = Post::factory()->create([
            'raw'         => '<p>raw content</p>',
            'fetched_raw' => '<p>fetched content</p>',
        ]);

        $this->assertStringContainsString('fetched content', $post->body);
        $this->assertStringNotContainsString('raw content', $post->body);
    }

    public function test_body_falls_back_to_raw_when_fetched_raw_is_null(): void
    {
        $post = Post::factory()->create([
            'raw'         => '<p>raw content</p>',
            'fetched_raw' => null,
        ]);

        $this->assertStringContainsString('raw content', $post->body);
    }

    // -------------------------------------------------------------------------
    // getPreviewAttribute()
    // -------------------------------------------------------------------------

    public function test_preview_returns_formatted_value_when_set_and_differs_from_raw(): void
    {
        $post = Post::factory()->create([
            'preview' => 'the preview text',
            'raw'     => '<p>completely different raw content</p>',
        ]);

        $this->assertStringContainsString('the preview text', $post->preview);
    }

    public function test_preview_truncates_body_to_50_words_when_preview_equals_raw(): void
    {
        $raw = '<p>' . implode(' ', array_map(fn ($i) => "word$i", range(1, 100))) . '</p>';

        $post = Post::factory()->create([
            'preview'     => $raw,
            'raw'         => $raw,
            'fetched_raw' => null,
        ]);

        $preview = $post->preview;

        $this->assertStringContainsString('word50', $preview);
        $this->assertStringNotContainsString('word51', $preview);
        $this->assertStringContainsString('...', $preview);
    }

    public function test_preview_generates_from_body_when_preview_is_null(): void
    {
        $post = Post::factory()->create([
            'preview'     => null,
            'raw'         => '<p>body content goes here</p>',
            'fetched_raw' => null,
        ]);

        $this->assertStringContainsString('body content goes here', $post->preview);
    }

    // -------------------------------------------------------------------------
    // Pruning
    // -------------------------------------------------------------------------

    public function test_prunable_scope_returns_only_posts_older_than_one_month(): void
    {
        $old    = Post::factory()->create(['created_at' => now()->subMonths(2)]);
        $recent = Post::factory()->create();

        $ids = (new Post)->prunable()->pluck('id');

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($recent->id, $ids);
    }

    public function test_pruning_creates_an_archived_post_and_deletes_the_post(): void
    {
        $post     = Post::factory()->create(['created_at' => now()->subMonths(2)]);
        $feedId   = $post->feed_id;
        $sourceId = $post->source_id;

        Artisan::call('model:prune', ['--model' => 'App\Models\Post']);

        $this->assertDatabaseHas('archived_posts', [
            'feed_id'   => $feedId,
            'source_id' => $sourceId,
        ]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_recent_posts_are_not_pruned(): void
    {
        $post = Post::factory()->create();

        Artisan::call('model:prune', ['--model' => 'App\Models\Post']);

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('archived_posts', 0);
    }
}
