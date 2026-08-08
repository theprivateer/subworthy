<?php

namespace App\Jobs;

use App\Fetchers\FetcherContract;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchFullPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var Post
     */
    private $post;

    /**
     * @var FetcherContract
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

        // Dispatched by id, not by model: the fetcher writes fetched_raw directly, so the
        // instance held here is already stale. SummarisePost re-reads the row when it runs
        // and picks up the enriched content.
        SummarisePost::dispatch($this->post->id);

        return $result;
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('FetchFullPost failed', [
            'post_id' => $this->post->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
