<?php

namespace Tests\Feature;

use App\Ai\Agents\PostSummariser;
use App\Jobs\CheckFeed;
use App\Jobs\FetchFullPost;
use App\Jobs\SummarisePost;
use App\Models\ArchivedPost;
use App\Models\Feed;
use App\Models\Post;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laminas\Feed\Reader\Reader;
use Tests\TestCase;

class CheckFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Reader::reset();
        PostSummariser::fake();
    }

    protected function tearDown(): void
    {
        Reader::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function bindMockResponse(string $xml): void
    {
        $mock  = new MockHandler([new Response(200, ['Content-Type' => 'application/rss+xml'], $xml)]);
        $stack = HandlerStack::create($mock);

        $this->app->bind(GuzzleClientInterface::class, fn () => new Client(['handler' => $stack]));
    }

    private function bind500Response(): void
    {
        // http_errors middleware is enabled by default — a 500 response becomes a ServerException.
        $mock  = new MockHandler([new Response(500)]);
        $stack = HandlerStack::create($mock);

        $this->app->bind(GuzzleClientInterface::class, fn () => new Client(['handler' => $stack]));
    }

    private function feedXml(array $items = [], string $title = 'Test Feed', string $link = 'https://example.com', string $description = 'A test feed'): string
    {
        $itemsXml = implode("\n", array_map(fn ($i) => $this->feedItem($i), $items));

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
              <channel>
                <title>{$title}</title>
                <link>{$link}</link>
                <description>{$description}</description>
                {$itemsXml}
              </channel>
            </rss>
            XML;
    }

    private function feedItem(array $data): string
    {
        $guid        = $data['guid'] ?? 'test-guid-' . uniqid();
        $title       = $data['title'] ?? 'Test Post';
        $link        = $data['link'] ?? 'https://example.com/post';
        $description = $data['description'] ?? 'Preview text';
        $content     = $data['content'] ?? '<p>Full post content</p>';
        $pubDate     = $data['pubDate'] ?? now()->subDay()->toRfc2822String();
        $enclosure   = isset($data['enclosure']) ? "<enclosure url=\"{$data['enclosure']['url']}\" type=\"{$data['enclosure']['type']}\" length=\"1234\"/>" : '';

        return <<<XML
            <item>
              <guid isPermaLink="false">{$guid}</guid>
              <title>{$title}</title>
              <link>{$link}</link>
              <description>{$description}</description>
              <content:encoded><![CDATA[{$content}]]></content:encoded>
              <pubDate>{$pubDate}</pubDate>
              {$enclosure}
            </item>
            XML;
    }

    // -------------------------------------------------------------------------
    // Post creation
    // -------------------------------------------------------------------------

    public function test_new_post_is_created_with_correct_fields(): void
    {
        $feed = Feed::factory()->create();

        $this->bindMockResponse($this->feedXml([
            [
                'guid'        => 'unique-guid-1',
                'title'       => 'My Post Title',
                'link'        => 'https://example.com/my-post',
                'description' => 'Post preview',
                'content'     => '<p>Post body</p>',
                'pubDate'     => now()->subHour()->toRfc2822String(),
            ],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseHas('posts', [
            'feed_id'   => $feed->id,
            'source_id' => 'unique-guid-1',
            'url'       => 'https://example.com/my-post',
            'title'     => 'My Post Title',
            'preview'   => 'Post preview',
            'raw'       => '<p>Post body</p>',
        ]);
    }

    public function test_archived_post_is_skipped(): void
    {
        $feed = Feed::factory()->create();

        ArchivedPost::factory()->create([
            'feed_id'   => $feed->id,
            'source_id' => 'archived-guid',
        ]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'archived-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_older_than_one_month_is_skipped(): void
    {
        $feed = Feed::factory()->create();

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'old-guid', 'pubDate' => now()->subMonths(2)->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_existing_post_is_not_duplicated(): void
    {
        $feed = Feed::factory()->create();

        Post::factory()->create([
            'feed_id'   => $feed->id,
            'source_id' => 'existing-guid',
        ]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'existing-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_refresh_posts_updates_existing_post_content(): void
    {
        $feed = Feed::factory()->create();

        Post::factory()->create([
            'feed_id'   => $feed->id,
            'source_id' => 'refresh-guid',
            'title'     => 'Old Title',
        ]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'refresh-guid', 'title' => 'Updated Title', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed, false, true);

        $this->assertDatabaseHas('posts', ['source_id' => 'refresh-guid', 'title' => 'Updated Title']);
        $this->assertDatabaseCount('posts', 1);
    }

    // -------------------------------------------------------------------------
    // First load — feed details
    // -------------------------------------------------------------------------

    public function test_first_load_updates_feed_title_link_description(): void
    {
        $feed = Feed::factory()->create(['title' => null, 'link' => null, 'description' => null]);

        $this->bindMockResponse($this->feedXml([], 'Imported Title', 'https://imported.example.com', 'Imported Description'));

        CheckFeed::dispatchSync($feed, true);

        $feed->refresh();
        $this->assertEquals('Imported Title', $feed->title);
        $this->assertEquals('https://imported.example.com', $feed->link);
        $this->assertEquals('Imported Description', $feed->description);
    }

    // -------------------------------------------------------------------------
    // next_check_at
    // -------------------------------------------------------------------------

    public function test_next_check_at_is_set_to_one_hour_on_success(): void
    {
        Carbon::setTestNow('2024-01-15 08:30:00');

        $feed = Feed::factory()->create();
        $this->bindMockResponse($this->feedXml());

        CheckFeed::dispatchSync($feed);

        $feed->refresh();
        $this->assertEquals('0930', $feed->next_check_at);

        Carbon::setTestNow();
    }

    public function test_next_check_at_is_set_to_15_minutes_on_failure(): void
    {
        Carbon::setTestNow('2024-01-15 08:30:00');

        $feed = Feed::factory()->create();
        $this->bind500Response();

        CheckFeed::dispatchSync($feed);

        $feed->refresh();
        $this->assertEquals('0845', $feed->next_check_at);

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // FetchFullPost dispatch
    // -------------------------------------------------------------------------

    public function test_fetch_full_post_is_dispatched_when_feed_has_a_fetcher(): void
    {
        Queue::fake([FetchFullPost::class]);

        $feed = Feed::factory()->create(['fetcher' => \App\Fetchers\ProducthuntFetcher::class]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'fetcher-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        Queue::assertPushed(FetchFullPost::class);
    }

    public function test_fetch_full_post_is_not_dispatched_when_feed_has_no_fetcher(): void
    {
        Queue::fake([FetchFullPost::class]);

        $feed = Feed::factory()->create(['fetcher' => null]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'no-fetcher-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        Queue::assertNotPushed(FetchFullPost::class);
    }

    public function test_summarise_post_is_dispatched_when_feed_has_no_fetcher(): void
    {
        Queue::fake([SummarisePost::class]);

        $feed = Feed::factory()->create(['fetcher' => null]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'summarise-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        Queue::assertPushed(SummarisePost::class);
    }

    public function test_summarise_post_is_not_dispatched_when_feed_has_a_fetcher(): void
    {
        Queue::fake([FetchFullPost::class, SummarisePost::class]);

        $feed = Feed::factory()->create(['fetcher' => \App\Fetchers\ProducthuntFetcher::class]);

        $this->bindMockResponse($this->feedXml([
            ['guid' => 'fetcher-summarise-guid', 'pubDate' => now()->subHour()->toRfc2822String()],
        ]));

        CheckFeed::dispatchSync($feed);

        Queue::assertNotPushed(SummarisePost::class);
    }

    // -------------------------------------------------------------------------
    // Audio enclosures
    // -------------------------------------------------------------------------

    public function test_audio_url_is_stored_for_audio_enclosure(): void
    {
        $feed = Feed::factory()->create();

        $this->bindMockResponse($this->feedXml([
            [
                'guid'      => 'audio-guid',
                'pubDate'   => now()->subHour()->toRfc2822String(),
                'enclosure' => ['url' => 'https://example.com/episode.mp3', 'type' => 'audio/mpeg'],
            ],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseHas('posts', [
            'source_id' => 'audio-guid',
            'audio_url' => 'https://example.com/episode.mp3',
        ]);
    }

    public function test_audio_url_is_not_set_for_non_audio_enclosure(): void
    {
        $feed = Feed::factory()->create();

        $this->bindMockResponse($this->feedXml([
            [
                'guid'      => 'video-guid',
                'pubDate'   => now()->subHour()->toRfc2822String(),
                'enclosure' => ['url' => 'https://example.com/video.mp4', 'type' => 'video/mp4'],
            ],
        ]));

        CheckFeed::dispatchSync($feed);

        $this->assertDatabaseMissing('posts', ['audio_url' => 'https://example.com/video.mp4']);
    }
}
