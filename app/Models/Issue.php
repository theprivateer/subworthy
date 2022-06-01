<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    use HasFactory, Prunable;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loadIssue(): void
    {
        $this->load('user');

        $active_subscriptions = $this->user->subscriptions()->pluck('feed_id')->all();

        // Order posts chronologically
        $posts = Post::with('feed')
                    ->whereIn('id', json_decode($this->getAttribute('posts')))
                    ->whereIn('feed_id', $active_subscriptions)
                    ->orderBy('published_at')
                    ->get();

        // TODO: Order feeds in a uniform way
        $posts = $posts->groupBy('feed_id');

        $user = $this->user;
        $posts = $posts->each(function ($item, $index) use ($user) {
            $subscription = Subscription::where('user_id', $user->id)
                                        ->where('feed_id', $item->first()->feed_id)
                                        ->first();

            $item->each(function ($article) use ($subscription) {
                $article->feed_title = $subscription->feed_title;
            });
        });

        // Sort by the order feeds were added - eventually a custom sort order
        $this->issue_posts = collect($posts)->sortKeys();
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }
}
