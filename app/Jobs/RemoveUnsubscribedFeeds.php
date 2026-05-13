<?php

namespace App\Jobs;

use App\Models\Feed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveUnsubscribedFeeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     *
     * NOTE: the actual work happens here in the constructor, not in handle(). This means
     * feed deletion runs at dispatch time in the calling process, not on the queue worker.
     */
    public function __construct()
    {
        $feeds = Feed::withCount('subscribers')->get();

        foreach($feeds as $feed)
        {
            if($feed->subscribers_count < 1)
            {
                $feed->delete();
            }
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
