<?php

namespace Tests\Feature;

use App\Jobs\RemoveUnsubscribedArticlesFromIssues;
use App\Models\Feed;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveUnsubscribedArticlesFromIssuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_from_unsubscribed_feeds_are_removed_from_issue(): void
    {
        $user             = User::factory()->create();
        $subscribedFeed   = Feed::factory()->create();
        $unsubscribedFeed = Feed::factory()->create();

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $subscribedFeed->id]);

        $activePost   = Post::factory()->create(['feed_id' => $subscribedFeed->id]);
        $inactivePost = Post::factory()->create(['feed_id' => $unsubscribedFeed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$activePost->id, $inactivePost->id]),
        ]);

        RemoveUnsubscribedArticlesFromIssues::dispatchSync($user);

        $issue->refresh();
        $postIds = json_decode($issue->posts);

        $this->assertContains($activePost->id, $postIds);
        $this->assertNotContains($inactivePost->id, $postIds);
    }

    public function test_posts_from_subscribed_feeds_are_retained(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        $post  = Post::factory()->create(['feed_id' => $feed->id]);
        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);

        RemoveUnsubscribedArticlesFromIssues::dispatchSync($user);

        $issue->refresh();
        $this->assertContains($post->id, json_decode($issue->posts));
    }

    public function test_all_issues_for_the_user_are_updated(): void
    {
        $user             = User::factory()->create();
        $subscribedFeed   = Feed::factory()->create();
        $unsubscribedFeed = Feed::factory()->create();

        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $subscribedFeed->id]);

        $activePost   = Post::factory()->create(['feed_id' => $subscribedFeed->id]);
        $inactivePost = Post::factory()->create(['feed_id' => $unsubscribedFeed->id]);

        $issue1 = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$activePost->id, $inactivePost->id]),
        ]);
        $issue2 = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$inactivePost->id]),
        ]);

        RemoveUnsubscribedArticlesFromIssues::dispatchSync($user);

        $this->assertNotContains($inactivePost->id, json_decode($issue1->fresh()->posts));
        $this->assertNotContains($inactivePost->id, json_decode($issue2->fresh()->posts));
    }

    public function test_issue_with_all_posts_removed_has_empty_json_array(): void
    {
        $user             = User::factory()->create();
        $unsubscribedFeed = Feed::factory()->create();

        $post  = Post::factory()->create(['feed_id' => $unsubscribedFeed->id]);
        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);

        RemoveUnsubscribedArticlesFromIssues::dispatchSync($user);

        $issue->refresh();
        $this->assertSame('[]', $issue->posts);
    }

    public function test_other_users_issues_are_not_affected(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $otherUser->id, 'feed_id' => $feed->id]);

        $post  = Post::factory()->create(['feed_id' => $feed->id]);
        $issue = Issue::factory()->create([
            'user_id' => $otherUser->id,
            'posts'   => json_encode([$post->id]),
        ]);

        // Run job for $user — should not touch $otherUser's issue.
        RemoveUnsubscribedArticlesFromIssues::dispatchSync($user);

        $issue->refresh();
        $this->assertContains($post->id, json_decode($issue->posts));
    }
}
