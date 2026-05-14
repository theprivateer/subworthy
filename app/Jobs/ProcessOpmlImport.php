<?php

namespace App\Jobs;

use App\Actions\SubscribeToFeed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Throwable;

class ProcessOpmlImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public string $path,
    ) {}

    public function handle(SubscribeToFeed $subscribeToFeed): void
    {
        $disk = Storage::disk('local');

        try {
            if( ! $disk->exists($this->path))
            {
                Log::warning('OPML import file was missing before processing', [
                    'user_id' => $this->userId,
                    'path' => $this->path,
                ]);

                return;
            }

            $urls = $this->extractOpmlFeedUrls($disk->get($this->path), $subscribeToFeed);

            foreach($urls as $url)
            {
                dispatch(new ImportOpmlFeed($this->userId, $url));
            }
        } catch (\InvalidArgumentException $e) {
            Log::warning('OPML import could not be parsed', [
                'user_id' => $this->userId,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::error('OPML import failed while dispatching feed imports', [
                'user_id' => $this->userId,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // The stored OPML file is only a queue hand-off artifact. Delete it even
            // when parsing fails so bad uploads cannot pile up in private storage.
            $disk->delete($this->path);
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractOpmlFeedUrls(string $contents, SubscribeToFeed $subscribeToFeed): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $opml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if( ! $opml instanceof SimpleXMLElement)
        {
            throw new \InvalidArgumentException('The uploaded OPML file could not be parsed.');
        }

        if(strtolower($opml->getName()) !== 'opml')
        {
            throw new \InvalidArgumentException('The uploaded file is not an OPML file.');
        }

        // OPML exports commonly represent folders as nested outline nodes. Recursing lets
        // users import the whole tree while still only using xmlUrl, the feed endpoint.
        $urls = $this->collectOutlineFeedUrls($opml, $subscribeToFeed);

        // De-duping in the parser job prevents repeated queue entries before the heavier
        // per-feed validation jobs start doing network work.
        return array_values(array_unique($urls));
    }

    /**
     * @return array<int, string>
     */
    private function collectOutlineFeedUrls(SimpleXMLElement $element, SubscribeToFeed $subscribeToFeed): array
    {
        $urls = [];

        foreach($element->children() as $child)
        {
            if($child->getName() === 'outline')
            {
                $xmlUrl = trim((string) $child->attributes()['xmlUrl']);

                if($xmlUrl !== '')
                {
                    $urls[] = $subscribeToFeed->normalizeFeedUrl($xmlUrl);
                }
            }

            $urls = array_merge($urls, $this->collectOutlineFeedUrls($child, $subscribeToFeed));
        }

        return $urls;
    }
}
