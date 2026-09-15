<?php

namespace Tests\Feature;

use App\Jobs\DownloadYoutubeVideo;
use App\Models\Feed;
use App\Models\Post;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadYoutubeVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_unique_by_post(): void
    {
        $job = new DownloadYoutubeVideo(123);

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame('123', $job->uniqueId());
    }

    public function test_it_downloads_the_video_and_marks_the_post_as_processed(): void
    {
        Storage::fake('local');
        Process::preventStrayProcesses();

        $feed = Feed::factory()->create(['title' => 'My Favourite Videos!']);
        $post = Post::factory()->for($feed)->create([
            'url' => 'https://www.youtube.com/watch?v=full-video',
        ]);
        $directory = Storage::disk('local')->path('youtube-videos/my-favourite-videos');
        $filepath = $directory.'/Downloaded video [full-video].mp4';
        Storage::disk('local')->put('youtube-videos/my-favourite-videos/Downloaded video [full-video].mp4', 'video');

        Process::fake([
            '*' => Process::result(output: $filepath.PHP_EOL),
        ]);

        (new DownloadYoutubeVideo($post->id))->handle();

        Process::assertRan(function (PendingProcess $process, ProcessResult $result) use ($post, $directory): bool {
            return $process->command === [
                'yt-dlp',
                '-t',
                'mp4',
                '--print',
                'after_move:filepath',
                '--no-playlist',
                $post->url,
            ]
                && $process->path === $directory
                && $process->timeout === 3500;
        });

        $post->refresh();

        $this->assertNotNull($post->processed_at);
        $this->assertSame('Downloaded video [full-video].mp4', $post->processed_filename);
    }

    public function test_it_does_not_process_shorts(): void
    {
        Process::fake();

        $post = Post::factory()->create([
            'url' => 'https://www.youtube.com/shorts/short-video',
        ]);

        (new DownloadYoutubeVideo($post->id))->handle();

        Process::assertNothingRan();
        $this->assertNull($post->fresh()->processed_at);
    }

    public function test_untitled_feeds_use_the_feed_id_as_the_directory_fallback(): void
    {
        Storage::fake('local');

        $feed = Feed::factory()->create(['title' => null]);
        $post = Post::factory()->for($feed)->create([
            'url' => 'https://www.youtube.com/watch?v=full-video',
        ]);
        $directory = Storage::disk('local')->path('youtube-videos/feed-'.$feed->id);
        $filepath = $directory.'/video.mp4';
        Storage::disk('local')->put('youtube-videos/feed-'.$feed->id.'/video.mp4', 'video');

        Process::fake([
            '*' => Process::result(output: $filepath.PHP_EOL),
        ]);

        (new DownloadYoutubeVideo($post->id))->handle();

        Process::assertRan(fn (PendingProcess $process) => $process->path === $directory);
    }

    public function test_it_does_not_download_an_already_processed_post(): void
    {
        Process::fake();

        $post = Post::factory()->create([
            'url' => 'https://www.youtube.com/watch?v=full-video',
            'processed_at' => now(),
            'processed_filename' => 'video.mp4',
        ]);

        (new DownloadYoutubeVideo($post->id))->handle();

        Process::assertNothingRan();
    }

    public function test_failed_download_does_not_mark_the_post_as_processed(): void
    {
        Storage::fake('local');
        Process::fake([
            '*' => Process::result(errorOutput: 'Download failed', exitCode: 1),
        ]);

        $post = Post::factory()->create([
            'url' => 'https://www.youtube.com/watch?v=full-video',
        ]);

        try {
            (new DownloadYoutubeVideo($post->id))->handle();
            $this->fail('The failed yt-dlp process should throw.');
        } catch (ProcessFailedException $exception) {
            $this->assertStringContainsString('Download failed', $exception->getMessage());
        }

        $post->refresh();

        $this->assertNull($post->processed_at);
        $this->assertNull($post->processed_filename);
    }
}
