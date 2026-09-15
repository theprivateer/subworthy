<?php

namespace Tests\Feature;

use App\Jobs\DownloadYoutubeVideo;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueYoutubeVideoDownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_only_unprocessed_full_youtube_videos(): void
    {
        Queue::fake();

        $video = Post::factory()->create([
            'url' => 'https://www.youtube.com/watch?v=full-video',
        ]);
        Post::factory()->create([
            'url' => 'https://www.youtube.com/shorts/short-video',
        ]);
        Post::factory()->create([
            'url' => 'https://www.youtube.com/watch',
        ]);
        Post::factory()->create([
            'url' => 'https://example.com/watch?v=article',
        ]);
        Post::factory()->create([
            'url' => 'https://www.youtube.com/watch?v=already-done',
            'processed_at' => now(),
            'processed_filename' => 'already-done.mp4',
        ]);

        $this->artisan('posts:download-youtube-videos')
            ->expectsOutputToContain('Dispatched 1 YouTube video download job.')
            ->assertSuccessful();

        Queue::assertPushed(DownloadYoutubeVideo::class, 1);
        Queue::assertPushed(
            DownloadYoutubeVideo::class,
            fn (DownloadYoutubeVideo $job) => $job->postId === $video->id,
        );
        $this->assertNull($video->fresh()->processed_at);
    }

    public function test_lookalike_youtube_domains_are_not_dispatched(): void
    {
        Queue::fake();

        Post::factory()->create([
            'url' => 'https://notyoutube.com/watch?v=spoofed',
        ]);

        $this->artisan('posts:download-youtube-videos')->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
