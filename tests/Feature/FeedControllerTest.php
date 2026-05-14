<?php

namespace Tests\Feature;

use App\Jobs\CheckFeed;
use App\Jobs\ProcessOpmlImport;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laminas\Feed\Reader\Reader;
use Tests\TestCase;

class FeedControllerTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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

    private function htmlWithFeedLinks(int $count): Response
    {
        $links = '';
        for ($i = 1; $i <= $count; $i++) {
            $links .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Feed {$i}\" href=\"https://example.com/feed{$i}.xml\">\n";
        }

        $html = "<html><head>{$links}</head><body></body></html>";

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    private function opmlUpload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('subscriptions.opml', $content);
    }

    // -------------------------------------------------------------------------
    // Valid feed URL
    // -------------------------------------------------------------------------

    public function test_valid_feed_url_creates_feed_and_subscription(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $this->bindMockResponses([$this->rssResponse()]);

        $this->actingAs($user)->post('/feed/create', ['url' => 'https://example.com/feed.rss']);

        $this->assertDatabaseHas('feeds', ['url' => 'https://example.com/feed.rss']);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id]);
    }

    public function test_valid_feed_url_dispatches_check_feed_and_redirects_home(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $this->bindMockResponses([$this->rssResponse()]);

        $this->actingAs($user)
            ->post('/feed/create', ['url' => 'https://example.com/feed.rss'])
            ->assertRedirect('/home');

        Queue::assertPushed(CheckFeed::class);
    }

    // -------------------------------------------------------------------------
    // Existing feed
    // -------------------------------------------------------------------------

    public function test_existing_feed_url_creates_only_a_subscription(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $feed = Feed::factory()->create([
            'url'               => 'https://example.com/feed.rss',
            'protocol_less_url' => 'example.com/feed.rss',
        ]);
        $this->bindMockResponses([$this->rssResponse()]);

        $this->actingAs($user)->post('/feed/create', ['url' => 'https://example.com/feed.rss']);

        $this->assertDatabaseCount('feeds', 1);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id, 'feed_id' => $feed->id]);
    }

    public function test_check_feed_is_not_dispatched_for_an_existing_feed(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        Feed::factory()->create([
            'url'               => 'https://example.com/feed.rss',
            'protocol_less_url' => 'example.com/feed.rss',
        ]);
        $this->bindMockResponses([$this->rssResponse()]);

        $this->actingAs($user)->post('/feed/create', ['url' => 'https://example.com/feed.rss']);

        Queue::assertNotPushed(CheckFeed::class);
    }

    // -------------------------------------------------------------------------
    // Already subscribed
    // -------------------------------------------------------------------------

    public function test_subscribing_to_already_subscribed_feed_shows_flash_and_no_duplicate(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $feed = Feed::factory()->create([
            'url'               => 'https://example.com/feed.rss',
            'protocol_less_url' => 'example.com/feed.rss',
        ]);
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $this->bindMockResponses([$this->rssResponse()]);

        $this->actingAs($user)->post('/feed/create', ['url' => 'https://example.com/feed.rss']);

        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertEquals('A Subscription to this Feed already exists', session('flash'));
    }

    // -------------------------------------------------------------------------
    // Feed link discovery (webpage fallback)
    // -------------------------------------------------------------------------

    public function test_when_exactly_one_feed_link_is_found_it_is_used_to_subscribe(): void
    {
        Queue::fake([CheckFeed::class]);

        $user = User::factory()->create();
        $this->bindMockResponses([
            new Response(500),                  // import() fails
            $this->htmlWithFeedLinks(1),        // findFeedLinks() returns 1 link
        ]);

        $this->actingAs($user)
            ->post('/feed/create', ['url' => 'https://example.com'])
            ->assertRedirect('/home');

        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id]);
    }

    public function test_when_multiple_feed_links_are_found_the_create_view_is_returned(): void
    {
        $user = User::factory()->create();
        $this->bindMockResponses([
            new Response(500),                  // import() fails
            $this->htmlWithFeedLinks(2),        // findFeedLinks() returns 2 links
        ]);

        $this->actingAs($user)
            ->post('/feed/create', ['url' => 'https://example.com'])
            ->assertViewIs('feed.create')
            ->assertViewHas('feedLinks');
    }

    // -------------------------------------------------------------------------
    // OPML import
    // -------------------------------------------------------------------------

    public function test_opml_import_stores_upload_and_dispatches_parser_job(): void
    {
        Queue::fake([ProcessOpmlImport::class]);
        Storage::fake('local');

        $user = User::factory()->create();
        $opml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
              <body>
                <outline text="One" xmlUrl="https://example.com/one.xml" />
                <outline text="Two" xmlUrl="https://example.com/two.xml" />
              </body>
            </opml>
            XML;

        $this->actingAs($user)
            ->post('/feed/import', ['opml' => $this->opmlUpload($opml)])
            ->assertRedirect('/home');

        Queue::assertPushed(ProcessOpmlImport::class, function (ProcessOpmlImport $job) use ($user) {
            Storage::disk('local')->assertExists($job->path);

            return $job->userId === $user->id
                && str_starts_with($job->path, 'opml-imports/');
        });
        $this->assertEquals('OPML import queued. Your subscriptions will appear as the import runs.', session('flash'));
    }

    public function test_opml_import_does_not_create_subscriptions_synchronously(): void
    {
        Queue::fake([ProcessOpmlImport::class]);
        Storage::fake('local');

        $user = User::factory()->create();
        $opml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
              <body>
                <outline text="Folder">
                  <outline text="Nested" xmlUrl="https://example.com/nested.xml" />
                </outline>
              </body>
            </opml>
            XML;

        $this->actingAs($user)
            ->post('/feed/import', ['opml' => $this->opmlUpload($opml)])
            ->assertRedirect('/home');

        $this->assertDatabaseCount('feeds', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_unauthenticated_users_cannot_import_opml(): void
    {
        Queue::fake([ProcessOpmlImport::class]);

        $opml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0"><body></body></opml>
            XML;

        $this->post('/feed/import', ['opml' => $this->opmlUpload($opml)])
            ->assertRedirect('/login');

        Queue::assertNotPushed(ProcessOpmlImport::class);
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    public function test_url_failure_returns_back_with_errors(): void
    {
        $user = User::factory()->create();
        $this->bindMockResponses([
            new Response(500),  // import() fails
            new Response(500),  // findFeedLinks() also fails
        ]);

        $this->actingAs($user)
            ->post('/feed/create', ['url' => 'https://example.com/feed.rss'])
            ->assertSessionHasErrors();
    }

    public function test_403_error_produces_friendly_error_message(): void
    {
        $user = User::factory()->create();
        $this->bindMockResponses([
            new Response(403),  // import() → 403
            new Response(403),  // findFeedLinks() → 403
        ]);

        $this->actingAs($user)
            ->post('/feed/create', ['url' => 'https://example.com/feed.rss'])
            ->assertSessionHasErrors();

        $errors = session('errors');
        $this->assertStringContainsString('403 Forbidden', $errors->first());
    }
}
