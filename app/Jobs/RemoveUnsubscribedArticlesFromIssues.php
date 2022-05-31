<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveUnsubscribedArticlesFromIssues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\User
     */
    private $user;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        //
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $issues = Issue::where('user_id', $this->user->id)->get();

        foreach($issues as $issue)
        {
            $active_subscriptions = $this->user->subscriptions()->pluck('feed_id')->all();

            $posts = Post::with('feed')
                ->whereIn('id', json_decode($issue->posts))
                ->whereIn('feed_id', $active_subscriptions)
                ->pluck('id')
                ->all();

            $issue->posts = json_encode($posts);

            $issue->save();
        }
    }
}
