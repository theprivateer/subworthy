<?php

namespace App\Jobs;

use App\Actions\SubscribeToFeed;
use App\Reader\GuzzleClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Reader;
use League\Uri\Uri;
use Throwable;

class ImportOpmlFeed implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public string $url,
    ) {}

    public function handle(SubscribeToFeed $subscribeToFeed): void
    {
        $url = $subscribeToFeed->normalizeFeedUrl($this->url);

        if( ! $this->isImportableFeedUrl($url))
        {
            Log::warning('OPML feed import skipped malformed URL', [
                'user_id' => $this->userId,
                'url' => $this->url,
            ]);

            return;
        }

        Reader::setHttpClient(new GuzzleClient());

        try {
            // This job validates that the OPML entry is actually a feed before creating
            // user-facing records. CheckFeed still owns metadata and post hydration.
            Reader::import($url);
        } catch (Throwable $e) {
            Log::warning('OPML feed import skipped unreadable feed', [
                'user_id' => $this->userId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $subscribeToFeed($this->userId, $url, checkFeedImmediately: true);
    }

    private function isImportableFeedUrl(string $url): bool
    {
        if( ! filter_var($url, FILTER_VALIDATE_URL))
        {
            return false;
        }

        $scheme = Uri::createFromString($url)->getScheme();

        return in_array($scheme, ['http', 'https'], true);
    }
}
