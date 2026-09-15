<?php

namespace App\Models;

use App\Formatters\DefaultFormatter;
use App\Formatters\FormatterContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use League\Uri\Uri;

class Post extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'feed_id',
        'source_id',
        'url',
        'title',
        'preview',
        'raw',
        'fetched_raw',
        'summary',
        'themes',
        'audio_url',
        'published_at',
        'modified_at',
        'processed_at',
        'processed_filename',
    ];

    protected function casts(): array
    {
        return [
            'themes' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function isYoutubeVideo(): bool
    {
        $uri = Uri::createFromString($this->url);
        $host = Str::lower($uri->getHost());
        parse_str($uri->getQuery(), $query);

        return ($host === 'youtube.com' || Str::endsWith($host, '.youtube.com'))
            && $uri->getPath() === '/watch'
            && filled($query['v'] ?? null);
    }

    public function isYoutubeShort(): bool
    {
        $uri = Uri::createFromString($this->url);
        $host = Str::lower($uri->getHost());

        return ($host === 'youtube.com' || Str::endsWith($host, '.youtube.com'))
            && preg_match('#^/shorts/[^/]+/?$#', $uri->getPath()) === 1;
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function readLaters(): HasMany
    {
        return $this->hasMany(ReadLater::class);
    }

    public function getBodyAttribute(): string
    {
        // fetched_raw holds enriched content from a custom FetcherContract implementation
        // (e.g. ProducthuntFetcher). Fall back to raw, which is the original RSS content.
        return $this->getFormatter()->render(
            $this->getAttribute('fetched_raw') ?? $this->getAttribute('raw')
        );
    }

    public function getPreviewAttribute($preview): string
    {
        if (empty($preview)) {
            $body = $this->getBodyAttribute();

            return Str::words(strip_tags($body), 50);
        }

        // Some feeds set the description element to the same value as the full content.
        // In that case, truncate the body rather than rendering a full-length "preview".
        if ($preview == $this->getAttribute('raw')) {
            $body = $this->getBodyAttribute();

            return Str::words(strip_tags($body), 50);
        }

        return $this->getFormatter()->render($preview);
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = $this->getAttribute('title');

        if (filled($title)) {
            return $title;
        }

        $content = $this->getAttribute('fetched_raw')
            ?? $this->getAttribute('raw')
            ?? $this->getAttribute('preview')
            ?? '';

        $content = Str::squish(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return filled($content) ? Str::limit($content, 50) : 'Untitled post';
    }

    /**
     * audio_url is taken from a feed enclosure, so the feed publisher controls it, and it is
     * interpolated into iframe src and anchor href attributes. Anything that is not a plain
     * http(s) URL is dropped rather than rendered, so a javascript: or data: enclosure cannot
     * become script. Applied on read so rows stored before this check are covered too.
     */
    public function getSafeAudioUrlAttribute(): ?string
    {
        return $this->httpUrlOrNull($this->getAttribute('audio_url'));
    }

    /**
     * url is the feed item's link, so it is likewise publisher-controlled. LinkController
     * redirects to it from a public route, which would otherwise let a feed publisher turn
     * this application's domain into a redirect to anywhere they like.
     */
    public function getSafeUrlAttribute(): ?string
    {
        return $this->httpUrlOrNull($this->getAttribute('url'));
    }

    private function httpUrlOrNull(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function getFormatter(): FormatterContract
    {
        if (is_null($this->feed->formatter)) {
            return new DefaultFormatter($this->feed);
        }

        $class = $this->feed->formatter;

        return new $class($this->feed);
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    /**
     * Prepare the model for pruning.
     *
     * @return void
     */
    protected function pruning()
    {
        ArchivedPost::create([
            'feed_id' => $this->getAttribute('feed_id'),
            'source_id' => $this->getAttribute('source_id'),
        ]);
    }
}
