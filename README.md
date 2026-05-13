# Subworthy

Subscribe to blogs, news sites and podcasts and get it all delivered to your inbox once a day in your own personalised newsletter.

## Requirements

- PHP 8.2+
- Node.js (for frontend assets)
- A database supported by Laravel (SQLite, MySQL, PostgreSQL)
- [Laravel Herd](https://herd.laravel.com/) (recommended for local development)

## Getting started

```shell
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Development

The site is served automatically by Laravel Herd at `https://subworthy.test`.

To process feeds and send issues, both the scheduler and queue worker must be running:

```shell
php artisan schedule:work      # runs the scheduler every minute
php artisan queue:work         # processes queued jobs
```

To rebuild frontend assets:

```shell
npm run dev       # dev server with HMR
npm run build     # production build
```

## Testing

```shell
php artisan test                        # full test suite
php artisan test --compact              # compact output
php artisan test --filter=ClassName     # single test class or method
```

## How it works

### Content pipeline

Feed checking and issue delivery are entirely queue-driven. A scheduler fires every minute:

1. Feeds whose `next_check_at` time matches the current UTC minute are dispatched as `CheckFeed` jobs.
2. **`CheckFeed`** — imports the RSS/Atom feed via `laminas/laminas-feed`. Creates or updates `Post` records. If the feed has a `fetcher` class configured, dispatches `FetchFullPost` for each new post. Advances `next_check_at` by one hour on success, or 15 minutes on failure.
3. **`FetchFullPost`** — runs a custom `FetcherContract` implementation to scrape richer content (e.g. `ProducthuntFetcher` extracts data from the page's Next.js JSON). Stores result in `Post.fetched_raw`.
4. Users whose `delivery_time` (UTC) matches the current minute, and whose `days_of_week` includes today, receive a `CreateDailyIssue` job.
5. **`CreateDailyIssue`** — collects posts since `last_delivered_at`, runs them through `PostFilterService`, stores surviving post IDs as JSON in a new `Issue` record, then dispatches `EmailDailyIssue`.
6. **`EmailDailyIssue`** — hydrates the issue's posts and sends the `NewIssue` mail notification.

Daily maintenance jobs prune `Post` and `Issue` records older than one month (pruned posts are archived to `ArchivedPost` as tombstones), and remove feeds that have no remaining subscribers.

### Feed extensibility

Two fields on `Feed` allow per-feed customisation:

- `Feed.formatter` — fully-qualified class implementing `FormatterContract`. Defaults to `DefaultFormatter`, which sanitises HTML via HTMLPurifier, adds `target="_blank"` to links, and resolves relative image URLs.
- `Feed.fetcher` — fully-qualified class implementing `FetcherContract`. Run as `FetchFullPost` after `CheckFeed`. See `ProducthuntFetcher` for an example.

### Filtering

`PostFilterService` evaluates per-subscription `Filter` records against each post. A filter has `field`, `operator`, and `pattern`. The operator (e.g. `contains`, `does_not_contain`, `regex`) maps to a method via `_camelCase` dynamic dispatch. A matching filter returns `true`, which excludes the post from the issue.

### Delivery schedule

`User.delivery_time_local` (e.g. `0800`) combined with `User.timezone` is converted to a UTC `delivery_time` on save and stored as a 4-character string. The scheduler matches that string against the current UTC `Hi`-format time each minute. `days_of_week` is a string of ISO day numbers (1–7).

### Data model

| Model | Notes |
|---|---|
| `User` | Auth, delivery schedule, timezone |
| `Feed` | Shared across users; holds RSS URL, optional formatter/fetcher class |
| `Subscription` | Joins User + Feed; optional title override; has many Filters |
| `Post` | Belongs to Feed; `raw` = original RSS HTML; `fetched_raw` = scraper output |
| `Issue` | Belongs to User; `posts` JSON column = included post IDs; `posts_excluded` = filtered-out IDs |
| `ArchivedPost` | Tombstone (feed_id + source_id) to prevent re-import after pruning |
| `ReadLater` | Joins User + Post for the read-later queue |
| `Filter` | Belongs to Subscription; field + operator + pattern |

### Public access

- `/@{username}` — public profile showing a user's issue archive
- `/issue/{issue}` — publicly viewable issue
- `/link/{user}/{post}` — link tracking redirect

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| PHP | 8.2+ |
| Frontend | Bootstrap 5, Alpine.js (via Livewire), Vite |
| Reactive UI | Livewire 3 |
| Feed parsing | laminas/laminas-feed |
| HTML sanitisation | ezyang/htmlpurifier |
| HTTP | Guzzle 7, Symfony BrowserKit/HttpClient |
| Spam protection | spatie/laravel-honeypot |
| Testing | PHPUnit 12 |
