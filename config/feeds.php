<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Refresh Posts
    |--------------------------------------------------------------------------
    |
    | When enabled, the CheckFeed job will re-import posts that have already
    | been seen, updating their content if it has changed. Disable this in
    | production to avoid unnecessary processing of previously imported posts.
    |
    */

    'refresh_posts' => env('REFRESH_POSTS', false),

    /*
    |--------------------------------------------------------------------------
    | Post Summary Minimum Word Count
    |--------------------------------------------------------------------------
    |
    | Posts with fewer words than this threshold keep their generated themes but
    | have their summary discarded — short posts and RSS previews rarely benefit
    | from a generated excerpt. Note that the post is still sent to the AI
    | provider, because themes are wanted for every post regardless of length.
    | Set POST_SUMMARY_MIN_WORDS in your .env to override. Defaults to 50.
    |
    */

    'summarise_min_words' => env('POST_SUMMARY_MIN_WORDS', 50),

    /*
    |--------------------------------------------------------------------------
    | Post Summary Maximum Characters
    |--------------------------------------------------------------------------
    |
    | Article content is truncated to this many characters before being sent to
    | the AI provider. This caps token spend on very long posts and prevents a
    | full-page scrape from overflowing the model's context window, which would
    | otherwise fail the job on every retry. A few thousand characters is ample
    | for a two or three sentence excerpt. Set POST_SUMMARY_MAX_CHARACTERS in
    | your .env to override. Defaults to 12000.
    |
    */

    'summarise_max_characters' => env('POST_SUMMARY_MAX_CHARACTERS', 12000),

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP Timeouts
    |--------------------------------------------------------------------------
    |
    | Applied to every feed fetch made through App\Reader\GuzzleClient. Guzzle
    | itself defaults both of these to 0, meaning no limit at all, which lets a
    | slow or hostile endpoint hold a request open indefinitely — including in
    | the web request that FeedController::store() serves. Values are seconds.
    |
    */

    'http_timeout' => env('FEED_HTTP_TIMEOUT', 10),

    'http_connect_timeout' => env('FEED_HTTP_CONNECT_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Block Private Outbound URLs
    |--------------------------------------------------------------------------
    |
    | Feed URLs come from users and from third-party OPML files, and are fetched
    | server-side. With this on, App\Reader\OutboundUrlGuard rejects any URL that
    | is not a publicly routable http(s) address — loopback, private ranges and
    | cloud metadata endpoints — on the initial request and on every redirect.
    | Set FEED_BLOCK_PRIVATE_URLS=false only in tests, where DNS resolution of
    | generated hostnames would be slow and non-deterministic. Defaults to true.
    |
    */

    'block_private_urls' => env('FEED_BLOCK_PRIVATE_URLS', true),

];
