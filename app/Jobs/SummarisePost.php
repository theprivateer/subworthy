<?php

namespace App\Jobs;

use App\Ai\Agents\PostSummariser;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SummarisePost implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * Takes an id rather than a Post so the job always reads the row as it stands when the
     * worker picks it up, not as it looked when the job was queued. Posts are pruned after a
     * month, so a queued job can outlive its post; loading by id lets handle() skip cleanly
     * instead of failing on a model that can no longer be resolved.
     */
    public function __construct(private int $postId) {}

    public function handle(): void
    {
        $defaultProvider = config('ai.default');
        if (blank(config("ai.providers.{$defaultProvider}.key"))) {
            return;
        }

        $post = Post::find($this->postId);

        if (! $post) {
            return;
        }

        $content = strip_tags($post->fetched_raw ?? $post->raw ?? '');

        if (blank($content)) {
            return;
        }

        // Word count is taken from the full content, but only a capped prefix is sent to the
        // provider — truncating first would let a long article fall under the threshold.
        $wordCount = $this->countWords($content);

        $prompt = Str::limit($content, (int) config('feeds.summarise_max_characters'), '');

        $response = (new PostSummariser)->prompt("Summarise this article:\n\n{$prompt}");

        $post->update([
            'summary' => $wordCount >= config('feeds.summarise_min_words') ? $response['summary'] : null,
            'themes' => $response['themes'],
        ]);
    }

    /**
     * Count words in a way that holds up outside ASCII.
     *
     * str_word_count() is byte-based, so it splits each multi-byte character into several
     * "words" — ten Cyrillic words score 50, inflating short non-English posts past the
     * threshold. Scripts written without spaces (CJK) are counted per character instead,
     * since whitespace tokenising would score a full paragraph as a single word.
     */
    private function countWords(string $content): int
    {
        $cjkPattern = '/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u';

        $cjkCharacters = preg_match_all($cjkPattern, $content);

        $remaining = preg_replace($cjkPattern, ' ', $content);

        $words = preg_split('/\s+/u', trim($remaining), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $cjkCharacters + count($words);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SummarisePost failed', [
            'post_id' => $this->postId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
