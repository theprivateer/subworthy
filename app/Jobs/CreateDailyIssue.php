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

        $since = $this->user->last_delivered_at ?: Carbon::now()->subDays(2);

        $posts_filtered = [];
        $posts_excluded = [];

        foreach($subscriptions as $subscription)
        {
            $posts = Post::where('feed_id', $subscription->feed_id)
                ->where('created_at', '>=', $since)
                ->get();

            // Do the filtering here...
            foreach($posts as $post)
            {
                // Apply the subscription filters
                // get a boolean response
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

        // Set this regardless of whether anything actually gets sent
        $this->user->last_delivered_at = Carbon::now();
        $this->user->save();

    }
}
