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

#[Signature('posts:backfill-summaries {--force : Process all posts, including those that already have a summary and themes} {--feed= : Feed ID to backfill, or "all" for all feeds (skips interactive prompt)} {--limit= : Maximum number of posts to dispatch (skips interactive prompt)}')]
#[Description('Backfill AI-generated summaries and themes for posts that are missing either')]
class BackfillPostSummaries extends Command
{
    public function handle(): int
    {
        $defaultProvider = config('ai.default');
        if (blank(config("ai.providers.{$defaultProvider}.key"))) {
            error('No AI provider API key is configured. Set ' . strtoupper($defaultProvider) . '_API_KEY in your .env file.');
            return self::FAILURE;
        }

        $feedId = $this->option('feed') ?? search(
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

        $limitOption = $this->option('limit');
        $limitInput = $limitOption ?? text(
            label: 'How many posts should be backfilled?',
            placeholder: 'Leave blank for all',
            validate: fn ($value) => filled($value) && (! is_numeric($value) || (int) $value < 1)
                ? 'Enter a whole number greater than zero, or leave blank for all.'
                : null,
        );

        $limit = filled($limitInput) ? (int) $limitInput : null;

        $query = Post::query()
            ->unless($this->option('force'), fn ($q) => $q->where(fn ($q) => $q->whereNull('summary')->orWhereNull('themes')))
            ->when($feedId !== 'all', fn ($q) => $q->where('feed_id', $feedId))
            ->oldest('published_at');

        if ($limit) {
            $query->limit($limit);
        }

        $dispatched = 0;
        $skipped = 0;

        $query->chunkById(100, function ($posts) use (&$dispatched, &$skipped) {
            foreach ($posts as $post) {
                $content = strip_tags($post->fetched_raw ?? $post->raw ?? '');

                if (blank($content)) {
                    $skipped++;
                    continue;
                }

                SummarisePost::dispatch($post);
                $dispatched++;
            }
        });

        info("Dispatched {$dispatched} " . str('job')->plural($dispatched) . " to generate summaries and themes, skipped {$skipped} " . str('post')->plural($skipped) . " with no content.");

        return self::SUCCESS;
    }
}
