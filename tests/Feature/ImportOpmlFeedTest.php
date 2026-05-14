<?php

namespace Tests\Feature;

use App\Actions\SubscribeToFeed;
use App\Jobs\CheckFeed;
use App\Jobs\ImportOpmlFeed;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Laminas\Feed\Reader\Reader;
use Tests\TestCase;

class ImportOpmlFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Reader::reset();
    }

    protected function tearDown(): void
    {
        Reader::reset();
        parent::tearDown();
    }

    private function bindMockResponses(array $responses): void
    {
        $mock  = new MockHandler($responses);
        $stack = HandlerStack::create($mock);

        $this->app->bind(GuzzleClientInterface::class, fn () => new Client(['handler' => $stack]));
    }

    private function rssResponse(): Response
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
              <channel>
                <title>Test Feed</title>
                <link>https://example.com</link>
                <description>A test feed</description>
              </channel>
            </rss>
            XML;

        return new Response(200, ['Content-Type' => 'application/rss+xml'], $xml);
    }

    public function test_import_opml_feed_creates_feed_and_subscription_for_valid_feed(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $this->bindMockResponses([$this->rssResponse(), $this->rssResponse()]);

        (new ImportOpmlFeed($user->id, 'https://example.com/feed.xml'))->handle(new SubscribeToFeed());

        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.com/feed.xml',
            'title' => 'Test Feed',
            'description' => 'A test feed',
        ]);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id]);
    }

    public function test_import_opml_feed_reuses_existing_feed_and_subscription(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $feed = Feed::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'protocol_less_url' => 'example.com/feed.xml',
        ]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $this->bindMockResponses([$this->rssResponse()]);

        (new ImportOpmlFeed($user->id, 'https://example.com/feed.xml'))->handle(new SubscribeToFeed());

        $this->assertDatabaseCount('feeds', 1);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_import_opml_feed_checks_new_feed_inline_without_queueing_check_feed(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $this->bindMockResponses([$this->rssResponse(), $this->rssResponse()]);

        (new ImportOpmlFeed($user->id, 'https://example.com/feed.xml'))->handle(new SubscribeToFeed());

        Queue::assertNotPushed(CheckFeed::class);
        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.com/feed.xml',
            'title' => 'Test Feed',
        ]);
    }

    public function test_import_opml_feed_does_not_dispatch_check_feed_for_existing_feed(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        Feed::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'protocol_less_url' => 'example.com/feed.xml',
        ]);
        $this->bindMockResponses([$this->rssResponse()]);

        (new ImportOpmlFeed($user->id, 'https://example.com/feed.xml'))->handle(new SubscribeToFeed());

        Queue::assertNotPushed(CheckFeed::class);
    }

    public function test_import_opml_feed_skips_malformed_url(): void
    {
        Queue::fake([CheckFeed::class]);
        Log::spy();

        $user = User::factory()->create();

        (new ImportOpmlFeed($user->id, 'not-a-url'))->handle(new SubscribeToFeed());

        $this->assertDatabaseCount('feeds', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Queue::assertNotPushed(CheckFeed::class);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_import_opml_feed_skips_network_validation_failure(): void
    {
        Queue::fake([CheckFeed::class]);
        Log::spy();

        $user = User::factory()->create();
        $this->bindMockResponses([new Response(500)]);

        (new ImportOpmlFeed($user->id, 'https://example.com/feed.xml'))->handle(new SubscribeToFeed());

        $this->assertDatabaseCount('feeds', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Queue::assertNotPushed(CheckFeed::class);
        Log::shouldHaveReceived('warning')->once();
    }
}
