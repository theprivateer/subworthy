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

class Post extends Model
{
    use HasFactory, Prunable;

    protected $guarded = [];

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
        return $this->getFormatter()->render(
            $this->getAttribute('fetched_raw') ?? $this->getAttribute('raw')
        );
    }

    public function getPreviewAttribute($preview): string
    {
        if(empty($preview))
        {
            $body = $this->getBodyAttribute();

            return Str::words(strip_tags($body), 50);
        }

        if($preview == $this->getAttribute('raw'))
        {
            $body = $this->getBodyAttribute();

            return Str::words(strip_tags($body), 50);
        }

        return $this->getFormatter()->render($preview);
    }

    private function getFormatter(): FormatterContract
    {
        if(is_null($this->feed->formatter))
        {
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
