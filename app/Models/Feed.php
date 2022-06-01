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

        if(strtolower($this->getAttribute('link')) == strtolower($this->getAttribute('link')))
        {
            return $this->getAttribute('tld');
        }

        return $this->getAttribute('link');
    }
}
