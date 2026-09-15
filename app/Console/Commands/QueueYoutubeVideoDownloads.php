<?php

namespace App\Console\Commands;

use App\Jobs\DownloadYoutubeVideo;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;

#[Signature('posts:download-youtube-videos')]
#[Description('Queue unprocessed full-length YouTube posts for offline download')]
class QueueYoutubeVideoDownloads extends Command
{
    public function handle(): int
    {
        $dispatched = 0;

        Post::query()
            ->whereNull('processed_at')
            ->where('url', 'like', '%youtube.com/watch%')
            ->chunkById(100, function ($posts) use (&$dispatched): void {
                foreach ($posts as $post) {
                    if (! $post->isYoutubeVideo()) {
                        continue;
                    }

                    DownloadYoutubeVideo::dispatch($post->id);
                    $dispatched++;
                }
            });

        info("Dispatched {$dispatched} YouTube video download ".str('job')->plural($dispatched).'.');

        return self::SUCCESS;
    }
}
