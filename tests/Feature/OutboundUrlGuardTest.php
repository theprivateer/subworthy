<?php

namespace Tests\Feature;

use App\Reader\OutboundUrlGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feed URLs are fetched server-side, so anything that is not publicly routable would turn
 * the app into a proxy into its own network.
 */
class OutboundUrlGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The suite disables enforcement so generated hostnames aren't resolved on every
        // fetch; this class is what proves the guard works, so it turns it back on.
        config(['feeds.block_private_urls' => true]);
    }

    public static function blockedUrls(): array
    {
        return [
            'aws/gcp metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback ipv4' => ['http://127.0.0.1/feed.xml'],
            'loopback name' => ['http://localhost:11434/'],
            'loopback ipv6' => ['http://[::1]/feed.xml'],
            'private 10/8' => ['http://10.0.0.5/feed.xml'],
            'private 172.16/12' => ['http://172.16.0.1/feed.xml'],
            'private 192.168/16' => ['http://192.168.1.1/feed.xml'],
            'link local ipv6' => ['http://[fe80::1]/feed.xml'],
            'unique local ipv6' => ['http://[fc00::1]/feed.xml'],
            'carrier grade nat' => ['http://100.64.0.1/feed.xml'],
            'protocol assignments' => ['http://192.0.0.1/feed.xml'],
            'benchmarking range' => ['http://198.18.0.1/feed.xml'],
            'unspecified' => ['http://0.0.0.0/feed.xml'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://127.0.0.1/'],
            'not a url' => ['just-a-string'],
            'no host' => ['http://'],
        ];
    }

    #[DataProvider('blockedUrls')]
    public function test_it_blocks_urls_that_are_not_publicly_routable(string $url): void
    {
        $this->assertFalse(OutboundUrlGuard::isFetchable($url), "{$url} should be blocked");
    }

    public function test_it_allows_a_public_https_url(): void
    {
        $this->assertTrue(OutboundUrlGuard::isFetchable('https://8.8.8.8/feed.xml'));
    }

    public function test_it_allows_a_public_ipv6_url(): void
    {
        $this->assertTrue(OutboundUrlGuard::isFetchable('https://[2606:4700::1]/feed.xml'));
    }

    public function test_assert_fetchable_names_the_scheme_it_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("scheme 'file' is not supported");

        OutboundUrlGuard::assertFetchable('file:///etc/passwd');
    }

    public function test_assert_fetchable_reports_unroutable_addresses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not publicly routable');

        OutboundUrlGuard::assertFetchable('http://169.254.169.254/');
    }
}
