<?php

namespace App\Actions;

use App\Jobs\CheckFeed;
use App\Models\Feed;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use League\Uri\Uri;

class SubscribeToFeed
{
    public function __invoke(int $userId, string $url, bool $checkFeedImmediately = false): Subscription
    {
        $url = $this->normalizeFeedUrl($url);
        $uri = Uri::createFromString($url);
        $scheme = $uri->getScheme();
        $protocolLessUrl = str_replace($scheme.'://', '', $url);

        $feed = Feed::where('protocol_less_url', $protocolLessUrl)->first();

        if (! $feed) {
            $feed = Feed::create([
                'url' => $url,
                'protocol_less_url' => $protocolLessUrl,
            ]);
        }

        $subscription = Subscription::firstOrCreate([
            'user_id' => $userId,
            'feed_id' => $feed->id,
        ]);

        if ($feed->wasRecentlyCreated === true) {
            // OPML entry jobs can run the first check inline so users do not briefly see
            // blank feed titles while another queued CheckFeed waits behind the import.
            if ($checkFeedImmediately) {
                try {
                    (new CheckFeed($feed, true))->handle();
                } catch (\Throwable $e) {
                    // The inline check is a cosmetic optimisation, not part of subscribing.
                    // CheckFeed now rethrows so its queued runs retry and record failures, so
                    // it is caught here — the subscription is already valid, and the scheduler
                    // picks the feed up on its own backoff.
                    Log::warning('Inline feed check failed during subscribe', [
                        'feed_id' => $feed->id,
                        'url' => $feed->url,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                dispatch(new CheckFeed($feed, true));
            }
        }

        return $subscription;
    }

    public function normalizeFeedUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }
}
