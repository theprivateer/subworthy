<?php

namespace App\Jobs;

use App\Fetchers\FetcherContract;
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
        return $this->fetcher->fetch($this->post);
    }
}
