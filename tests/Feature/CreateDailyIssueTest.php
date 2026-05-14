<?php

namespace Tests\Feature;

use App\Jobs\CreateDailyIssue;
use App\Jobs\EmailDailyIssue;
use App\Models\Feed;
use App\Models\Filter;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateDailyIssueTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Issue creation
    // -------------------------------------------------------------------------

    public function test_issue_is_created_when_new_posts_exist(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        $this->assertDatabaseHas('issues', ['user_id' => $user->id]);
    }

    public function test_issue_is_not_created_when_no_new_posts_exist(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        // No posts created.

        CreateDailyIssue::dispatchSync($user);

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_issue_post_ids_are_stored_as_json(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $post = Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        $issue = Issue::where('user_id', $user->id)->first();
        $this->assertContains($post->id, json_decode($issue->posts));
    }

    // -------------------------------------------------------------------------
    // Edition incrementing
    // -------------------------------------------------------------------------

    public function test_edition_increments_from_previous_issue(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        Issue::factory()->create(['user_id' => $user->id, 'edition' => 3]);

        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        $latest = Issue::where('user_id', $user->id)->orderByDesc('edition')->first();
        $this->assertEquals(4, $latest->edition);
    }

    public function test_first_issue_edition_is_1(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        $this->assertDatabaseHas('issues', ['user_id' => $user->id, 'edition' => 1]);
    }

    // -------------------------------------------------------------------------
    // Filter exclusions
    // -------------------------------------------------------------------------

    public function test_filtered_post_is_excluded_from_issue_and_tracked(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user         = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed         = Feed::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        Filter::factory()->create([
            'subscription_id' => $subscription->id,
            'field'           => 'title',
            'operator'        => 'contains',
            'pattern'         => 'exclude me',
        ]);

        $excludedPost = Post::factory()->create([
            'feed_id'    => $feed->id,
            'title'      => 'Please exclude me',
            'created_at' => now()->subHour(),
        ]);

        $includedPost = Post::factory()->create([
            'feed_id'    => $feed->id,
            'title'      => 'Normal post title',
            'created_at' => now()->subHour(),
        ]);

        CreateDailyIssue::dispatchSync($user);

        $issue = Issue::where('user_id', $user->id)->first();
        $this->assertContains($includedPost->id, json_decode($issue->posts));
        $this->assertContains($excludedPost->id, json_decode($issue->posts_excluded));
    }

    public function test_no_issue_is_created_when_all_posts_are_filtered_out(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user         = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed         = Feed::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        Filter::factory()->create([
            'subscription_id' => $subscription->id,
            'field'           => 'title',
            'operator'        => 'contains',
            'pattern'         => 'exclude me',
        ]);

        Post::factory()->create([
            'feed_id'    => $feed->id,
            'title'      => 'Please exclude me',
            'created_at' => now()->subHour(),
        ]);

        CreateDailyIssue::dispatchSync($user);

        $this->assertDatabaseCount('issues', 0);
        Queue::assertNotPushed(EmailDailyIssue::class);
    }

    // -------------------------------------------------------------------------
    // last_delivered_at
    // -------------------------------------------------------------------------

    public function test_last_delivered_at_is_updated_even_when_no_issue_was_created(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $before = now()->subDay();
        $user   = User::factory()->create(['last_delivered_at' => $before]);

        // No subscriptions — nothing to include.

        CreateDailyIssue::dispatchSync($user);

        $user->refresh();
        $this->assertTrue($user->last_delivered_at->isAfter($before));
    }

    public function test_first_run_looks_back_two_days_when_last_delivered_at_is_null(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => null]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        // Post created 1 day ago — within the 2-day lookback.
        $post = Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subDay()]);

        CreateDailyIssue::dispatchSync($user);

        $issue = Issue::where('user_id', $user->id)->first();
        $this->assertNotNull($issue);
        $this->assertContains($post->id, json_decode($issue->posts));
    }

    // -------------------------------------------------------------------------
    // EmailDailyIssue dispatch
    // -------------------------------------------------------------------------

    public function test_email_daily_issue_job_is_dispatched_when_issue_is_created(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        Post::factory()->create(['feed_id' => $feed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        Queue::assertPushed(EmailDailyIssue::class);
    }

    public function test_email_daily_issue_job_is_not_dispatched_when_no_posts(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user = User::factory()->create(['last_delivered_at' => now()->subDay()]);

        CreateDailyIssue::dispatchSync($user);

        Queue::assertNotPushed(EmailDailyIssue::class);
    }

    // -------------------------------------------------------------------------
    // Only own feed posts are included
    // -------------------------------------------------------------------------

    public function test_only_posts_from_subscribed_feeds_are_included(): void
    {
        Queue::fake([EmailDailyIssue::class]);

        $user           = User::factory()->create(['last_delivered_at' => now()->subDay()]);
        $subscribedFeed = Feed::factory()->create();
        $otherFeed      = Feed::factory()->create();

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $subscribedFeed->id]);

        $ownPost   = Post::factory()->create(['feed_id' => $subscribedFeed->id, 'created_at' => now()->subHour()]);
        $otherPost = Post::factory()->create(['feed_id' => $otherFeed->id, 'created_at' => now()->subHour()]);

        CreateDailyIssue::dispatchSync($user);

        $issue = Issue::where('user_id', $user->id)->first();
        $postIds = json_decode($issue->posts);

        $this->assertContains($ownPost->id, $postIds);
        $this->assertNotContains($otherPost->id, $postIds);
    }
}
