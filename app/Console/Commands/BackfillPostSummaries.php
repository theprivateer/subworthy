<?php

namespace App\Console\Commands;

use App\Jobs\SummarisePost;
use App\Models\Feed;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

#[Signature('posts:backfill-summaries')]
#[Description('Backfill AI-generated summaries for posts that do not yet have one')]
class BackfillPostSummaries extends Command
{
    public function handle(): int
    {
        $defaultProvider = config('ai.default');
        if (blank(config("ai.providers.{$defaultProvider}.key"))) {
            error('No AI provider API key is configured. Set ' . strtoupper($defaultProvider) . '_API_KEY in your .env file.');
            return self::FAILURE;
        }

        $feedId = search(
            label: 'Which feed should be backfilled?',
            options: function (string $value) {
                $query = Feed::orderBy('title');

                if (strlen($value) > 0) {
                    $query->where('title', 'like', "%{$value}%");
                }

                return array_merge(
                    ['all' => 'All feeds'],
                    $query->limit(20)->pluck('title', 'id')->all(),
                );
            },
            placeholder: 'Type to search, or select All feeds',
        );

        $limitInput = text(
            label: 'How many posts should be backfilled?',
            placeholder: 'Leave blank for all',
            validate: fn ($value) => filled($value) && (! is_numeric($value) || (int) $value < 1)
                ? 'Enter a whole number greater than zero, or leave blank for all.'
                : null,
        );

        $limit = filled($limitInput) ? (int) $limitInput : null;
        $minWords = config('feeds.summarise_min_words');

        $query = Post::query()
            ->whereNull('summary')
            ->when($feedId !== 'all', fn ($q) => $q->where('feed_id', $feedId))
            ->oldest('published_at');

        if ($limit) {
            $query->limit($limit);
        }

        $dispatched = 0;
        $skipped = 0;

        $query->chunkById(100, function ($posts) use (&$dispatched, &$skipped, $minWords) {
            foreach ($posts as $post) {
                $content = strip_tags($post->fetched_raw ?? $post->raw ?? '');

                if (str_word_count($content) < $minWords) {
                    $skipped++;
                    continue;
                }

                SummarisePost::dispatch($post);
                $dispatched++;
            }
        });

        info("Dispatched {$dispatched} " . str('job')->plural($dispatched) . ", skipped {$skipped} " . str('post')->plural($skipped) . " below the {$minWords}-word threshold.");

        return self::SUCCESS;
    }
}
