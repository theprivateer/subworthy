<?php

namespace App\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Laminas\Feed\Reader\Http\ClientInterface as FeedReaderHttpClientInterface;
use Laminas\Feed\Reader\Http\Psr7ResponseDecorator;
use Psr\Http\Message\UriInterface;

class GuzzleClient implements FeedReaderHttpClientInterface
{
    /**
     * @var GuzzleClientInterface
     */
    private $client;

    public function __construct(?GuzzleClientInterface $client = null)
    {
        if ($client) {
            $this->client = $client;
        } elseif (app()->bound(GuzzleClientInterface::class)) {
            $this->client = app(GuzzleClientInterface::class);
        } else {
            // Guzzle defaults both timeouts to 0, meaning no limit. FeedController::store()
            // fetches inside the web request, so an unbounded read holds a PHP-FPM worker for
            // as long as a hostile endpoint cares to trickle bytes.
            $this->client = new Client([
                'timeout' => (int) config('feeds.http_timeout', 10),
                'connect_timeout' => (int) config('feeds.http_connect_timeout', 5),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($uri): Psr7ResponseDecorator
    {
        OutboundUrlGuard::assertFetchable((string) $uri);

        return new Psr7ResponseDecorator(
            $this->client->request('GET', $uri, [
                // Validating only the submitted URL would be trivially bypassed by a public
                // host that 302s to an internal one, so every hop is re-checked.
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => false,
                    'on_redirect' => function ($request, $response, UriInterface $target) {
                        OutboundUrlGuard::assertFetchable((string) $target);
                    },
                ],
            ])
        );
    }
}
