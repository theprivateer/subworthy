<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use League\Uri\Uri;

class Feed extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // link is the feed's website homepage; url is the RSS/Atom feed URL.
            // Prefer the homepage when available so relative image paths resolve correctly.
            $uri = Uri::createFromString($model->link ?? $model->url);

            $model->tld = $uri->getScheme() . '://' . $uri->getHost();
        });
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getWebsiteAttribute()
    {
        if(empty($this->getAttribute('link')))
        {
            return $this->getAttribute('tld');
        }

        // BUG: this compares link to itself so it is always true, meaning tld is always
        // returned and the link attribute is never used as the website URL.
        if(strtolower($this->getAttribute('link')) == strtolower($this->getAttribute('link')))
        {
            return $this->getAttribute('tld');
        }

        return $this->getAttribute('link');
    }
}
