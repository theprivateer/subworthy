<?php

namespace Tests\Feature;

use App\Reader\GuzzleClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use ReflectionClass;
use Tests\TestCase;

class GuzzleClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['feeds.block_private_urls' => true]);
    }

    private function configOf(Client $client): array
    {
        $property = (new ReflectionClass($client))->getProperty('config');
        $property->setAccessible(true);

        return $property->getValue($client);
    }

    /**
     * Guzzle defaults both timeouts to 0 (no limit). FeedController::store() fetches inside
     * the web request, so an unbounded read would hold a worker open indefinitely.
     */
    public function test_the_default_client_sets_both_timeouts(): void
    {
        $reader = new GuzzleClient;

        $property = (new ReflectionClass($reader))->getProperty('client');
        $property->setAccessible(true);

        $config = $this->configOf($property->getValue($reader));

        $this->assertSame(10, $config['timeout']);
        $this->assertSame(5, $config['connect_timeout']);
    }

    public function test_timeouts_are_configurable(): void
    {
        config(['feeds.http_timeout' => 3, 'feeds.http_connect_timeout' => 2]);

        $reader = new GuzzleClient;

        $property = (new ReflectionClass($reader))->getProperty('client');
        $property->setAccessible(true);

        $config = $this->configOf($property->getValue($reader));

        $this->assertSame(3, $config['timeout']);
        $this->assertSame(2, $config['connect_timeout']);
    }

    public function test_it_refuses_to_fetch_an_internal_address(): void
    {
        $inner = $this->createMock(ClientInterface::class);
        $inner->expects($this->never())->method('request');

        $this->expectException(InvalidArgumentException::class);

        (new GuzzleClient($inner))->get('http://169.254.169.254/latest/meta-data/');
    }

    public function test_it_refuses_a_non_http_scheme(): void
    {
        $inner = $this->createMock(ClientInterface::class);
        $inner->expects($this->never())->method('request');

        $this->expectException(InvalidArgumentException::class);

        (new GuzzleClient($inner))->get('file:///etc/passwd');
    }

    /**
     * A public host that redirects to an internal one would defeat a check applied only to
     * the submitted URL, so every hop is revalidated.
     */
    public function test_it_validates_each_redirect_hop(): void
    {
        $captured = null;

        $inner = $this->createMock(ClientInterface::class);
        $inner->method('request')->willReturnCallback(
            function ($method, $uri, $options) use (&$captured) {
                $captured = $options['allow_redirects'];

                return new Response(200, [], '<rss></rss>');
            }
        );

        (new GuzzleClient($inner))->get('https://8.8.8.8/feed.xml');

        $this->assertIsCallable($captured['on_redirect']);
        $this->assertSame(['http', 'https'], $captured['protocols']);

        $this->expectException(InvalidArgumentException::class);

        ($captured['on_redirect'])(
            null,
            null,
            new Uri('http://169.254.169.254/latest/meta-data/')
        );
    }
}
