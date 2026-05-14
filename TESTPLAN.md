# Test Plan

This document lists all testable surfaces in the application, grouped by concern. It covers what to test and why, but not the implementation. Tests should be PHPUnit feature tests unless noted otherwise.

Factories will need to be created for `Feed`, `Post`, `Subscription`, `Filter`, `Issue`, `ReadLater`, and `ArchivedPost` before most of these tests can be implemented.

---

## 1. Unit Tests

These are pure logic tests with no HTTP or database involvement.

### 1.1 PostFilterService (`app/Filters/PostFilterService.php`)

The filter service maps operator strings to methods via dynamic dispatch. Each operator should be tested in isolation.

- `contains` — matches case-insensitively anywhere in the value
- `contains` — returns false when pattern is absent
- `does not contain` — inverse of `contains`
- `equals` — case-insensitive exact match
- `does not equal` — inverse of `equals`
- `regex()` — returns true when the pattern matches
- `regex no match` — inverse of `regex`
- Unknown operator — returns false without error (method does not exist)
- Empty filter collection — `filter()` returns false (post is included)
- Multiple filters — any single match causes exclusion (OR logic, not AND)
- Filter applies to the correct field (`title`, `preview`, `raw`)

### 1.2 DefaultFormatter (`app/Formatters/DefaultFormatter.php`)

- `render()` strips disallowed HTML tags
- `render()` adds `target="_blank"` to all anchor tags
- `render()` resolves root-relative image paths (`/images/foo.jpg` → `https://example.com/images/foo.jpg`)
- `render()` leaves protocol-relative image paths untouched (`//cdn.example.com/...`)
- `render()` leaves absolute image URLs untouched
- Non-ASCII characters in content are preserved (UTF-8 encoding)

### 1.3 User model boot logic (`app/Models/User.php`)

The `boot` method converts `delivery_time_local` + `timezone` to a UTC `delivery_time` on save.

- UTC timezone with `0800` local stores `0800` as `delivery_time`
- `Australia/Sydney` (UTC+11) with `0800` local stores `2100` as `delivery_time` (previous day UTC)
- Saving with no timezone defaults to `UTC`
- Saving with no `delivery_time_local` defaults to `0000`
- `hasDefaultDeliverySettings()` returns true when timezone is UTC and delivery time is `0000`
- `hasDefaultDeliverySettings()` returns false when either field differs from the default

---

## 2. Content Pipeline — Jobs

These are feature tests that hit the database. Queue should be set to `sync` for job tests.

### 2.1 CheckFeed (`app/Jobs/CheckFeed.php`)

- New posts are created with correct fields (`source_id`, `title`, `url`, `preview`, `raw`, `published_at`)
- Posts already in `archived_posts` are skipped (not re-imported)
- Posts older than one month are skipped
- Existing posts are not duplicated (upsert via `source_id` + `feed_id`)
- When `refresh_posts = true`, existing post content is updated
- `firstLoad = true` triggers feed title/link/description to be saved
- `next_check_at` is set to one hour ahead on success
- `next_check_at` is set to 15 minutes ahead on failure
- When the feed has a `fetcher` class, `FetchFullPost` is dispatched for each new post
- Audio RSS enclosures are detected and `audio_url` is stored
- Non-audio enclosures do not set `audio_url`

### 2.2 FetchFullPost (`app/Jobs/FetchFullPost.php`)

- Delegates to the `FetcherContract` implementation's `fetch()` method
- The fetcher receives the correct `Post` model

### 2.3 CreateDailyIssue (`app/Jobs/CreateDailyIssue.php`)

- Collects posts from the user's subscribed feeds since `last_delivered_at`
- For a user with no `last_delivered_at`, looks back 2 days
- Posts passing all filters are included in the issue (`posts` column)
- Posts excluded by filters are stored in `posts_excluded`
- An `Issue` record is created with the correct `user_id`, `edition`, and `issue_date`
- Edition increments correctly when prior issues exist
- No issue is created when all posts are filtered out
- `EmailDailyIssue` is dispatched after a non-empty issue is created
- `EmailDailyIssue` is NOT dispatched when no issue is created
- `last_delivered_at` is updated regardless of whether an issue was created
- Posts from unsubscribed feeds are not included

### 2.4 EmailDailyIssue (`app/Jobs/EmailDailyIssue.php`)

- Sends a `NewIssue` notification to the issue's user
- The notification uses the mail channel
- The email subject contains the issue's edition number
- The email is sent to the user's email address

### 2.5 RemoveUnsubscribedArticlesFromIssues (`app/Jobs/RemoveUnsubscribedArticlesFromIssues.php`)

- Removes post IDs from existing issues that belong to feeds the user has unsubscribed from
- Post IDs from still-active subscriptions remain in the issue
- Issues with no remaining posts after cleanup have an empty `posts` JSON array (not corrupted)
- Works correctly across multiple issues for the same user

### 2.6 RemoveUnsubscribedFeeds (`app/Jobs/RemoveUnsubscribedFeeds.php`)

- Deletes feeds that have no subscribers
- Does not delete feeds that have at least one subscriber
- Feeds with multiple subscribers are not deleted when one subscriber unsubscribes

---

## 3. Scheduler Logic (`bootstrap/app.php`)

The scheduler closure dispatches jobs based on time and day matching.

- `CheckFeed` is dispatched for feeds whose `next_check_at` matches the current time
- Feeds with a null `next_check_at` are not dispatched
- `CreateDailyIssue` is dispatched for users whose `delivery_time` matches the current time
- Users not scheduled for the current day of the week are skipped
- Users with a non-null `paused` value are skipped
- Users on a matching day and time are dispatched

---

## 4. Model Behaviour

### 4.1 Post model (`app/Models/Post.php`)

- `getBodyAttribute()` returns the formatted `fetched_raw` content when present
- `getBodyAttribute()` falls back to `raw` when `fetched_raw` is null
- `getPreviewAttribute()` returns the preview field when set and different from `raw`
- `getPreviewAttribute()` truncates the body to 50 words when preview equals `raw`
- `getPreviewAttribute()` generates a preview from body when preview is empty
- `pruning()` creates an `ArchivedPost` record with `feed_id` and `source_id`
- `prunable()` scope only returns posts older than one month

### 4.2 Feed model (`app/Models/Feed.php`)

- `tld` is computed from `link` when available, falling back to `url`
- `tld` is updated on save when `link` or `url` changes
- **Known bug**: `getWebsiteAttribute()` always returns `tld` due to a self-comparison (`link == link` is always true); a test would document the current (broken) behaviour and make the bug visible

### 4.3 Subscription model (`app/Models/Subscription.php`)

- `getFeedTitleAttribute()` returns the subscription's custom `title` when set
- `getFeedTitleAttribute()` returns the feed's title when no custom title is set

### 4.4 Issue model (`app/Models/Issue.php`)

- `loadIssue()` hydrates `issue_posts` grouped by `feed_id`
- `loadIssue()` excludes posts from feeds the user has since unsubscribed from
- `loadIssue()` injects `feed_title` from the subscription override when present
- `loadIssue()` falls back to the feed's title when no subscription override is set
- `prunable()` scope only returns issues older than one month

### 4.5 User model (`app/Models/User.php`)

- `subscriptions()` returns the user's subscriptions
- `issues()` returns the user's issues
- `readLaters()` returns the user's read-later items
- `logInteraction()` updates `last_interaction_at` to now

---

## 5. HTTP Controllers

All protected routes require an authenticated and email-verified user. Tests should assert 302 redirects to login for unauthenticated requests.

### 5.1 HomeController

- Authenticated, verified user sees their subscriptions and last 7 issues
- Unauthenticated request redirects to login
- Unverified user is redirected to email verification notice

### 5.2 FeedController

- Valid feed URL creates a `Feed` and `Subscription`, dispatches `CheckFeed`, redirects home
- Valid URL for an already-existing feed creates only a `Subscription` (no duplicate feed)
- Subscribing to a feed the user already subscribes to shows "already exists" flash, no duplicate subscription
- When the URL is a webpage (not a feed), and exactly one feed link is found, uses that URL instead
- When multiple feed links are found, the `feed.create` view is returned with options
- When the URL fails entirely, a validation error is shown
- 403 errors produce a friendly error message
- `CheckFeed` is only dispatched for newly created feed records, not for existing ones

### 5.3 SubscriptionController

- `edit` shows the subscription edit view with the feed and filters loaded
- `edit` with another user's subscription returns 404 (model binding)
- `update` changes the subscription title
- `update` accepts a null title (clears the override)
- `update` rejects titles over 255 characters
- `destroy` deletes the subscription and dispatches `RemoveUnsubscribedArticlesFromIssues`
- `destroy` redirects to home

### 5.4 FilterController

- `store` creates a filter attached to the correct subscription
- `store` rejects requests with missing `field`, `operator`, or `pattern`
- `update` changes the filter's field, operator, and pattern
- `destroy` deletes the filter

### 5.5 ReadLaterController

- `index` redirects to home when the user has no read-later items
- `index` shows posts grouped by feed
- `index` applies subscription title overrides
- `destroy` removes the read-later entry for the given post
- `destroy` only removes the authenticated user's entry (not another user's)

### 5.6 IssueController

- `show` is publicly accessible without authentication
- `show` loads and displays the issue's posts
- `show` logs an interaction on the issue's owner
- Authenticated user viewing their own issue has `authUser` set
- Guest viewing an issue has `authUser` set to false
- Authenticated user viewing another user's issue has `authUser` set to them (not the issue owner)

### 5.7 LinkController

- `show` logs an interaction on the user
- `show` redirects to the post's URL

### 5.8 UserController

- `show` with a valid username displays the public profile
- `show` with an unknown username returns 404
- `edit` returns the account edit view with timezone and time slot data
- `update` changes the user's email address
- `update` changes the user's username
- `update` rejects a duplicate email address
- `update` rejects a duplicate username
- `destroy` logs out the user, deletes the account, and redirects to `/cancelled`
- `destroy` requires password confirmation middleware

### 5.9 DeliveryController

- `update` saves the timezone, delivery time, and days of week
- `update` rejects an invalid timezone string
- `update` rejects a delivery time that is not 4 digits
- `update` rejects day values outside 1–7
- An empty `days_of_week` array is accepted and stored as an empty string

### 5.10 PasswordController

- `update` hashes and saves the new password
- `update` rejects passwords shorter than 8 characters
- `update` rejects mismatched `password` and `password_confirmation`

---

## 6. Livewire — Article Component (`app/Livewire/Article.php`)

- Mount shows the read-later button when the authenticated user is viewing their own issue
- Mount does not show the read-later button for guests
- Mount does not show the read-later button when viewing another user's issue
- Mount marks `readingLater = true` when the post is already in the user's read-later list
- `showFull()` sets `fullArticle = true` and dispatches `postOpened` event
- `showPreview()` sets `fullArticle = false`
- `readLater()` creates a `ReadLater` record for the authenticated user
- `readLater()` sets `readingLater = true`
- `readLater()` returns 403 when called by a guest
- `readLater()` returns 403 when called by a user viewing someone else's issue
- `removeReadLater()` deletes the `ReadLater` record
- `removeReadLater()` sets `readingLater = false`
- `removeReadLater()` returns 403 when called by an unauthorised user

---

## 7. Public Routes

- `GET /` redirects to the home route
- `GET /cancelled` renders the cancellation view (no auth required)
- `GET /@{username}` displays the public profile (no auth required)
- `GET /issue/{issue}` displays the issue (no auth required)
- `GET /link/{user}/{post}` logs interaction and redirects (no auth required)

---

## 8. Pruning & Archiving

- Running `model:prune` for `Post` creates `ArchivedPost` records for each pruned post
- Pruned posts cannot be re-imported by `CheckFeed` (archived tombstone blocks it)
- Running `model:prune` for `Issue` removes issues older than one month
- `ArchivedPost` records are not themselves pruned

---

## 9. Notifications

- `NewIssue` notification uses the mail channel
- Subject line is `Subworthy, Issue {edition}`
- The mail uses the `mail.issue` Markdown template
- `issue_posts`, `user`, and `issue` are passed correctly to the view
