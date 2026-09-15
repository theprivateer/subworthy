<?php

namespace App\Actions;

use App\Models\Subscription;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;

class ExportSubscriptions
{
    public function __invoke(int $userId): StreamedResponse
    {
        return response()->streamDownload(function () use ($userId): void {
            $writer = new XMLWriter;
            $writer->openUri('php://output');
            $writer->startDocument('1.0', 'UTF-8');
            $writer->setIndent(true);

            $writer->startElement('opml');
            $writer->writeAttribute('version', '2.0');
            $writer->startElement('head');
            $writer->writeElement('title', 'Subworthy subscriptions');
            $writer->endElement();
            $writer->startElement('body');

            $subscriptions = Subscription::query()
                ->where('subscriptions.user_id', $userId)
                ->join('feeds', 'feeds.id', '=', 'subscriptions.feed_id')
                ->select([
                    'subscriptions.title as subscription_title',
                    'feeds.title as source_title',
                    'feeds.url as feed_url',
                    'feeds.link as website_url',
                ])
                ->orderBy('subscriptions.id')
                ->cursor();

            foreach ($subscriptions as $subscription) {
                $title = $subscription->subscription_title
                    ?: $subscription->source_title
                    ?: $subscription->feed_url;

                $writer->startElement('outline');
                $writer->writeAttribute('text', $title);
                $writer->writeAttribute('title', $title);
                $writer->writeAttribute('type', 'rss');
                $writer->writeAttribute('xmlUrl', $subscription->feed_url);

                if (! empty($subscription->website_url)) {
                    $writer->writeAttribute('htmlUrl', $subscription->website_url);
                }

                $writer->endElement();
            }

            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();
        }, 'subworthy-subscriptions.opml', [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
