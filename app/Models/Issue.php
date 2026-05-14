<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'user_id',
        'edition',
        'issue_date',
        'posts',
        'posts_excluded',
    ];

    public function casts(): array
    {
        return [
            'issue_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loadIssue(): void
    {
        $this->load('user');

        // Filter to currently-active subscriptions so posts from feeds the user has
        // since unsubscribed from don't appear in an older issue they're re-reading.
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

        // Pre-load all relevant subscriptions keyed by feed_id to avoid N+1 queries.
        $subscriptions = Subscription::where('user_id', $user->id)
            ->whereIn('feed_id', $posts->keys())
            ->get()
            ->keyBy('feed_id');

        // Inject feed_title onto each post object so the view can show per-subscription
        // title overrides without an extra query per post. This mutates the model instances
        // in memory rather than going through a relation.
        $posts = $posts->each(function ($item, $index) use ($subscriptions) {
            $subscription = $subscriptions->get($item->first()->feed_id);

            $item->each(function ($article) use ($subscription) {
                $article->feed_title = $subscription?->feed_title;
            });
        });

        // sortKeys() orders groups by feed_id (integer), which approximates the order
        // feeds were subscribed to since IDs are auto-incrementing.
        // TODO: eventually a custom sort order
        $this->issue_posts = collect($posts)->sortKeys();
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }
}
