<?php

namespace App\Jobs;

use App\Models\ArchivedPost;
use App\Models\Feed;
use App\Models\Post;
use App\Reader\GuzzleClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laminas\Feed\Reader\Reader;

class CheckFeed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Feed
     */
    private $feed;
    /**
     * @var bool
     */
    private $firstLoad;

    private $result;

    private $purifier;
    /**
     * @var false
     */
    private $refresh_posts;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Feed $feed
     * @param bool $firstLoad
     */
    public function __construct(Feed $feed, $firstLoad = false, $refresh_posts = false)
    {
        $this->feed = $feed;
        $this->firstLoad = $firstLoad;
        $this->refresh_posts = $refresh_posts;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start = Carbon::now();

        Reader::setHttpClient(new GuzzleClient());

        try
        {
            $this->result = Reader::import($this->feed->url);

            if($this->firstLoad || is_null($this->feed->title)) $this->updateFeedDetails();

            foreach($this->result as $post)
            {
                $this->processPost($post);
            }

            // next_check_at stores a 4-digit 24-hour time string (Hi format, e.g. "0930"),
            // not a full datetime. The scheduler compares it against the current UTC time string.
            $this->feed->next_check_at = $start->addHour()->format('Hi');

        } catch (\Exception $e)
        {
            // On failure, back off 15 minutes rather than a full hour to retry sooner.
            $this->feed->next_check_at = $start->addMinutes(15)->format('Hi');
        }

        $this->feed->save();
    }

    private function updateFeedDetails()
    {
        $this->feed->title = $this->result->getTitle();
        $this->feed->link = $this->result->getLink();
        $this->feed->description = $this->result->getDescription();

        $this->feed->save();
    }

    private function processPost($data)
    {
        // Stops us from repopulating old posts that have been pruned
        $exists = ArchivedPost::query()
                            ->where('source_id', $data->getId())
                            ->where('feed_id', $this->feed->id)
                            ->get();

        if($exists->count())
        {
            return;
        }

        // Skip posts older than a month — matches the pruning window — so that a first-load
        // of a feed with years of history doesn't flood the queue or the database.
        // TODO: set the pruning duration in config
        if(Carbon::parse($data->getDateCreated())->lte(now()->subMonth()))
        {
            return;
        }

        $post = Post::query()
                    ->where('source_id', $data->getId())
                    ->where('feed_id', $this->feed->id)
                    ->first();

        // $refresh_posts forces re-importing content for existing posts, used when a
        // formatter or fetcher changes and existing post bodies need to be regenerated.
        if ( ! $post || $this->refresh_posts)
        {
            $raw = $data->getContent();

            $preview = $data->getDescription();

            // TODO: find any relative paths and add full domain

            $insert = [
                'feed_id'       => $this->feed->id,
                'source_id'     => $data->getId(),
                'url'           => $data->getLink(),
                'title'         => $data->getTitle(),
                'preview'       => trim($preview),
                'raw'           => trim($raw), // keep the raw HTML for re-parsing/purifying later
                'published_at'  => Carbon::parse($data->getDateCreated()),
                'modified_at'   => Carbon::parse($data->getDateModified()),
            ];

            if($this->isAudioRSS($data))
            {
                $insert['audio_url'] = optional($data->getEnclosure())->url;
            }


            if( ! $post)
            {
                $post = Post::create($insert);
            } else
            {
                $post->update($insert);
            }

            // If there is a custom fetcher for this feed, trigger that now
            if($this->feed->fetcher)
            {
                dispatch(new FetchFullPost($post, (new $this->feed->fetcher)));
            }
        }
    }

    private function isAudioRSS($post)
    {
        if ( ! $post->getEnclosure())
        {
            return false;
        }

        if(strpos($post->getEnclosure()->type, 'audio') !== false)
        {
            return true;
        }

        return false;
    }
}
