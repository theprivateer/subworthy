<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // loadIssue()
    // -------------------------------------------------------------------------

    public function test_load_issue_groups_posts_by_feed_id(): void
    {
        $user  = User::factory()->create();
        $feed1 = Feed::factory()->create();
        $feed2 = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed1->id]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed2->id]);
        $post1 = Post::factory()->create(['feed_id' => $feed1->id]);
        $post2 = Post::factory()->create(['feed_id' => $feed2->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post1->id, $post2->id]),
        ]);
        $issue->loadIssue();

        $this->assertCount(2, $issue->issue_posts);
        $this->assertTrue($issue->issue_posts->has($feed1->id));
        $this->assertTrue($issue->issue_posts->has($feed2->id));
    }

    public function test_load_issue_excludes_posts_from_unsubscribed_feeds(): void
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
        $issue->loadIssue();

        $ids = $issue->issue_posts->flatten()->pluck('id');
        $this->assertContains($activePost->id, $ids);
        $this->assertNotContains($inactivePost->id, $ids);
    }

    public function test_load_issue_injects_subscription_title_override_onto_posts(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create(['title' => 'Original Feed Title']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'title'   => 'My Override',
        ]);
        $post = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);
        $issue->loadIssue();

        $hydratedPost = $issue->issue_posts->flatten()->first();
        $this->assertEquals('My Override', $hydratedPost->feed_title);
    }

    public function test_load_issue_falls_back_to_feed_title_when_no_subscription_override(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create(['title' => 'Feed Name']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'title'   => null,
        ]);
        $post = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);
        $issue->loadIssue();

        $hydratedPost = $issue->issue_posts->flatten()->first();
        $this->assertEquals('Feed Name', $hydratedPost->feed_title);
    }

    // -------------------------------------------------------------------------
    // Pruning
    // -------------------------------------------------------------------------

    public function test_prunable_scope_returns_only_issues_older_than_one_month(): void
    {
        $old    = Issue::factory()->create(['created_at' => now()->subMonths(2)]);
        $recent = Issue::factory()->create();

        $ids = (new Issue)->prunable()->pluck('id');

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($recent->id, $ids);
    }
}
