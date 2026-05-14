<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_title_returns_custom_title_when_set(): void
    {
        $subscription = Subscription::factory()->create(['title' => 'My Custom Title']);

        $this->assertEquals('My Custom Title', $subscription->feed_title);
    }

    public function test_feed_title_falls_back_to_feed_title_when_no_custom_title(): void
    {
        $feed         = Feed::factory()->create(['title' => 'Feed Name']);
        $subscription = Subscription::factory()->create([
            'feed_id' => $feed->id,
            'title'   => null,
        ]);

        $this->assertEquals('Feed Name', $subscription->feed_title);
    }

    public function test_custom_title_takes_precedence_over_feed_title(): void
    {
        $feed         = Feed::factory()->create(['title' => 'Feed Name']);
        $subscription = Subscription::factory()->create([
            'feed_id' => $feed->id,
            'title'   => 'Override',
        ]);

        $this->assertEquals('Override', $subscription->feed_title);
        $this->assertNotEquals('Feed Name', $subscription->feed_title);
    }
}
