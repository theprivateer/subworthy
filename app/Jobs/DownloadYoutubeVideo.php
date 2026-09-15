<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DownloadYoutubeVideo implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3600;

    public int $uniqueFor = 86400;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        $post = Post::with('feed')->find($this->postId);

        if (! $post || $post->processed_at || ! $post->isYoutubeVideo()) {
            return;
        }

        $disk = Storage::disk('local');
        $feedSlug = Str::slug($post->feed->title ?? '');
        $directory = 'youtube-videos/'.($feedSlug ?: 'feed-'.$post->feed_id);
        $disk->makeDirectory($directory);

        $result = Process::path($disk->path($directory))
            ->timeout(3500)
            ->run([
                'yt-dlp',
                '-t',
                'mp4',
                '--print',
                'after_move:filepath',
                '--no-playlist',
                $post->url,
            ])
            ->throw();

        $path = Str::of($result->output())->trim()->afterLast("\n")->trim()->toString();
        $filename = basename($path);

        if (blank($filename) || ! $disk->exists($directory.'/'.$filename)) {
            throw new RuntimeException('yt-dlp completed without creating the expected video file.');
        }

        $post->update([
            'processed_at' => now(),
            'processed_filename' => $filename,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function uniqueId(): string
    {
        return (string) $this->postId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('YouTube video download failed', [
            'post_id' => $this->postId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
