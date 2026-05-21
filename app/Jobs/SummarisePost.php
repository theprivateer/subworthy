<?php

namespace App\Jobs;

use App\Ai\Agents\PostSummariser;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SummarisePost implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private Post $post) {}

    public function handle(): void
    {
        $defaultProvider = config('ai.default');
        if (blank(config("ai.providers.{$defaultProvider}.key"))) {
            return;
        }

        $content = strip_tags($this->post->fetched_raw ?? $this->post->raw ?? '');

        if (str_word_count($content) < config('feeds.summarise_min_words')) {
            return;
        }

        $response = (new PostSummariser)->prompt("Summarise this article:\n\n{$content}");

        $this->post->update(['summary' => $response->text]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SummarisePost failed', [
            'post_id' => $this->post->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
