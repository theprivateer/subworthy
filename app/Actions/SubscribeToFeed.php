<?php

namespace App\Actions;

use App\Jobs\CheckFeed;
use App\Models\Feed;
use App\Models\Subscription;
use League\Uri\Uri;

class SubscribeToFeed
{
    public function __invoke(int $userId, string $url, bool $checkFeedImmediately = false): Subscription
    {
        $url = $this->normalizeFeedUrl($url);
        $uri = Uri::createFromString($url);
        $scheme = $uri->getScheme();
        $protocolLessUrl = str_replace($scheme . '://', '', $url);

        $feed = Feed::where('protocol_less_url', $protocolLessUrl)->first();

        if( ! $feed)
        {
            $feed = Feed::create([
                'url' => $url,
                'protocol_less_url' => $protocolLessUrl,
            ]);
        }

        $subscription = Subscription::firstOrCreate([
            'user_id' => $userId,
            'feed_id' => $feed->id,
        ]);

        if($feed->wasRecentlyCreated === true)
        {
            // OPML entry jobs can run the first check inline so users do not briefly see
            // blank feed titles while another queued CheckFeed waits behind the import.
            if($checkFeedImmediately)
            {
                (new CheckFeed($feed, true))->handle();
            } else
            {
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
