# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this app does

Subworthy is a Laravel 8 RSS/podcast aggregator that collects content from subscribed feeds and delivers it as a personalised daily newsletter email. Users subscribe to feed URLs, configure delivery schedules and per-subscription filters, and receive a single daily digest.

## Development environment

This project runs under Laravel Herd. The scheduler and queue worker must be running to process feeds and dispatch issues:

```shell
php artisan schedule:work      # runs the scheduler every minute
php artisan queue:work         # processes queued jobs
```

Frontend assets use Laravel Mix:

```shell
npm run dev       # one-time build
npm run watch     # watch mode
```

## Common commands

```shell
php artisan migrate            # run migrations
php artisan test               # run full test suite
php artisan test --filter=Foo  # run a single test class or method
./vendor/bin/phpunit           # alternative test runner
php artisan tinker             # REPL
```

## Architecture

### Content pipeline

Feed checking and issue delivery are entirely queue-driven. The scheduler fires every minute and dispatches jobs based on stored timestamps:

1. **`CheckFeed` job** — imports an RSS/Atom feed via `laminas/laminas-feed` using a custom Guzzle HTTP client. Creates or updates `Post` records. If the feed has a `fetcher` class set, dispatches `FetchFullPost` for each post. Updates `Feed.next_check_at` to the next hour on success, or +15 minutes on failure.
2. **`FetchFullPost` job** — runs a custom `FetcherContract` implementation to scrape additional content (e.g. `ProducthuntFetcher` scrapes Next.js JSON from the page). Stores result in `Post.fetched_raw`.
3. **`CreateDailyIssue` job** — collects posts since the user's `last_delivered_at`, runs them through `PostFilterService`, stores surviving post IDs as JSON in an `Issue` record, then dispatches `EmailDailyIssue`.
4. **`EmailDailyIssue` job** — calls `Issue::loadIssue()` to hydrate `issue_posts`, then sends the `NewIssue` mail notification using the `mail.issue` Markdown template.

The scheduler (`app/Console/Kernel.php`) also runs `model:prune` daily — both `Post` and `Issue` are pruned after one month. Pruned posts are archived to `ArchivedPost` (feed_id + source_id only) to prevent re-importing.

### Feed extensibility

Two fields on `Feed` allow per-feed customisation:
- `Feed.formatter` — fully-qualified class name implementing `FormatterContract`. If null, `DefaultFormatter` is used, which runs content through `HTMLPurifier`, adds `target="_blank"` to links, and resolves relative image URLs.
- `Feed.fetcher` — fully-qualified class name implementing `FetcherContract`. Dispatched as `FetchFullPost` after `CheckFeed` stores the raw RSS content. See `ProducthuntFetcher` for an example.

### Filtering

`PostFilterService` evaluates per-subscription `Filter` records against each post. A filter has `field`, `operator`, and `pattern`. The operator (e.g. `contains`, `does_not_contain`, `regex`) maps to a method via `_camelCase` dynamic dispatch. If any filter returns true, the post is excluded from the issue.

### User delivery schedule

`User.delivery_time_local` (e.g. `0800`) combined with `User.timezone` is converted to UTC `delivery_time` on save. The scheduler matches `delivery_time` against the current UTC minute. `days_of_week` is a string of ISO day numbers (1–7); `strpos` checks whether the current day is included.

### Data model summary

- `User` → many `Subscription`, `Issue`, `ReadLater`
- `Feed` → many `Subscription` (shared across users)
- `Subscription` → belongs to `User` + `Feed`; has many `Filter`; `title` overrides `Feed.title` when set
- `Post` → belongs to `Feed`; `raw` holds original RSS HTML; `fetched_raw` holds scraper output
- `Issue` → belongs to `User`; `posts` JSON column holds included post IDs; `posts_excluded` holds filtered-out IDs
- `ArchivedPost` — tombstone record (feed_id + source_id) to block re-import of pruned posts

### Frontend

Bootstrap 5 + Alpine.js 2. SCSS compiled via Laravel Mix from `resources/scss/style.scss`. Livewire 2 is used for the `Article` component (`app/Http/Livewire/Article.php`) which handles inline article expansion and read-later toggling without page reloads.

### Public profile

Users have a public profile at `/@{username}` showing their issue archive. Issues are also publicly accessible at `/issue/{issue}`. Link tracking goes through `/link/{user}/{post}`.
