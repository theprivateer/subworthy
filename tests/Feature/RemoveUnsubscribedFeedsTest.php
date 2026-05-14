<?php

namespace Tests\Feature;

use App\Jobs\RemoveUnsubscribedFeeds;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveUnsubscribedFeedsTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_with_no_subscribers_is_deleted(): void
    {
        $feed = Feed::factory()->create();

        RemoveUnsubscribedFeeds::dispatchSync();

        $this->assertDatabaseMissing('feeds', ['id' => $feed->id]);
    }

    public function test_feed_with_at_least_one_subscriber_is_retained(): void
    {
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['feed_id' => $feed->id]);

        RemoveUnsubscribedFeeds::dispatchSync();

        $this->assertDatabaseHas('feeds', ['id' => $feed->id]);
    }

    public function test_only_unsubscribed_feeds_are_deleted(): void
    {
        $subscribedFeed   = Feed::factory()->create();
        $unsubscribedFeed = Feed::factory()->create();

        Subscription::factory()->create(['feed_id' => $subscribedFeed->id]);

        RemoveUnsubscribedFeeds::dispatchSync();

        $this->assertDatabaseHas('feeds', ['id' => $subscribedFeed->id]);
        $this->assertDatabaseMissing('feeds', ['id' => $unsubscribedFeed->id]);
    }

    public function test_feed_with_subscription_from_multiple_users_is_retained(): void
    {
        $feed  = Feed::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Subscription::factory()->create(['feed_id' => $feed->id, 'user_id' => $user1->id]);
        Subscription::factory()->create(['feed_id' => $feed->id, 'user_id' => $user2->id]);

        RemoveUnsubscribedFeeds::dispatchSync();

        $this->assertDatabaseHas('feeds', ['id' => $feed->id]);
    }
}
