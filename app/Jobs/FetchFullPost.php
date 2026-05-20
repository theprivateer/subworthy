<?php

namespace App\Jobs;

use App\Fetchers\FetcherContract;
use App\Jobs\SummarisePost;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchFullPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @var \App\Models\Post
     */
    private $post;
    /**
     * @var \App\Fetchers\FetcherContract
     */
    private $fetcher;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post, FetcherContract $fetcher)
    {
        $this->post = $post;
        $this->fetcher = $fetcher;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = $this->fetcher->fetch($this->post);

        SummarisePost::dispatch($this->post->fresh());

        return $result;
    }

    public function failed(?\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('FetchFullPost failed', [
            'post_id' => $this->post->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
