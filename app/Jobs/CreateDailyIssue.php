<?php

namespace App\Jobs;

use App\Filters\PostFilterService;
use App\Models\Subscription;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CreateDailyIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get a list of all feeds that belong to this daily
        $subscriptions = Subscription::with('filters')
                                    ->where('user_id', $this->user->id)
                                    ->get();

        // For users who have never received an issue, look back 2 days so their first
        // delivery isn't empty. After that, last_delivered_at is always set (see below).
        $since = $this->user->last_delivered_at ?: Carbon::now()->subDays(2);

        $posts_filtered = [];
        $posts_excluded = [];

        foreach($subscriptions as $subscription)
        {
            $posts = Post::where('feed_id', $subscription->feed_id)
                ->where('created_at', '>=', $since)
                ->get();

            // PostFilterService::filter() returns true when a post should be EXCLUDED.
            // $posts_filtered = posts that survived all filters (included in the issue).
            // $posts_excluded = posts that matched a filter rule (omitted from the issue).
            foreach($posts as $post)
            {
                if(PostFilterService::filter($post, $subscription->filters))
                {
                    $posts_excluded[] = $post->id;
                } else {
                    $posts_filtered[] = $post->id;
                }
            }
        }


        if(count($posts_filtered))
        {
            $previousEdition = DB::table('issues')->where('user_id', $this->user->id)->max('edition');

            $issue = Issue::create([
                'user_id' => $this->user->id,
                'edition' => ($previousEdition ?? 0) + 1,
                'issue_date' => Carbon::now()->timezone($this->user->timezone),
                'posts' => json_encode($posts_filtered),
                'posts_excluded' => json_encode($posts_excluded),
            ]);

            // Now we can send the email
            dispatch( new EmailDailyIssue($issue));
        }

        // Always advance last_delivered_at even when no issue was created, so the
        // next run doesn't accumulate a growing lookback window of unsent posts.
        $this->user->last_delivered_at = Carbon::now();
        $this->user->save();

    }
}
