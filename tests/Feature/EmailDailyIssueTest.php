<?php

namespace Tests\Feature;

use App\Jobs\EmailDailyIssue;
use App\Models\Feed;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\NewIssue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailDailyIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_issue_notification_is_sent_to_user(): void
    {
        Notification::fake();

        $user         = User::factory()->create();
        $feed         = Feed::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $post         = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);

        EmailDailyIssue::dispatchSync($issue);

        Notification::assertSentTo($user, NewIssue::class);
    }

    public function test_notification_contains_the_correct_issue(): void
    {
        Notification::fake();

        $user  = User::factory()->create();
        $feed  = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $post  = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'edition' => 7,
            'posts'   => json_encode([$post->id]),
        ]);

        EmailDailyIssue::dispatchSync($issue);

        Notification::assertSentTo(
            $user,
            NewIssue::class,
            fn (NewIssue $notification) => $notification->toMail($user)->subject === 'Subworthy, Issue 7'
        );
    }

    public function test_notification_is_sent_to_the_users_email_address(): void
    {
        Notification::fake();

        $user  = User::factory()->create(['email' => 'reader@example.com']);
        $feed  = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $post  = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'posts'   => json_encode([$post->id]),
        ]);

        EmailDailyIssue::dispatchSync($issue);

        Notification::assertSentTo($user, NewIssue::class);
        $this->assertSame('reader@example.com', $user->routeNotificationFor('mail'));
    }
}
