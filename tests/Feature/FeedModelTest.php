<?php

namespace Tests\Feature;

use App\Models\Feed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // tld computation (boot saving hook)
    // -------------------------------------------------------------------------

    public function test_tld_is_derived_from_link_when_set(): void
    {
        $feed = Feed::factory()->create([
            'link' => 'https://blog.example.com/welcome',
            'url'  => 'https://feeds.other.com/rss',
        ]);

        $this->assertEquals('https://blog.example.com', $feed->tld);
    }

    public function test_tld_falls_back_to_url_when_link_is_null(): void
    {
        $feed = Feed::factory()->create([
            'link' => null,
            'url'  => 'https://feeds.example.com/rss',
        ]);

        $this->assertEquals('https://feeds.example.com', $feed->tld);
    }

    public function test_tld_is_recomputed_when_link_changes(): void
    {
        $feed = Feed::factory()->create([
            'link' => 'https://original.com/page',
            'url'  => 'https://original.com/feed',
        ]);

        $feed->update(['link' => 'https://updated.com/page']);

        $this->assertEquals('https://updated.com', $feed->tld);
    }

    public function test_tld_is_recomputed_when_url_changes_and_link_is_null(): void
    {
        $feed = Feed::factory()->create([
            'link' => null,
            'url'  => 'https://original.com/feed',
        ]);

        $feed->update(['url' => 'https://updated.com/feed']);

        $this->assertEquals('https://updated.com', $feed->tld);
    }

    // -------------------------------------------------------------------------
    // getWebsiteAttribute() — known bug documented
    // -------------------------------------------------------------------------

    public function test_website_returns_tld_when_link_is_empty(): void
    {
        $feed = Feed::factory()->create(['link' => null]);

        $this->assertEquals($feed->tld, $feed->website);
    }

    public function test_website_always_returns_tld_even_when_link_is_set(): void
    {
        // BUG: getWebsiteAttribute() contains a self-comparison (link == link) that is
        // always true, so tld is always returned and the link URL is never reached.
        // This test documents the current broken behaviour. The correct return value
        // would be the full link URL ('https://blog.example.com/welcome').
        $feed = Feed::factory()->create([
            'link' => 'https://blog.example.com/welcome',
            'url'  => 'https://feeds.example.com/rss',
        ]);

        $this->assertEquals($feed->tld, $feed->website);
    }
}
