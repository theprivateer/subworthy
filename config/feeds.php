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
    | Posts with fewer words than this threshold will be skipped during AI
    | summarisation. Short posts and RSS previews rarely benefit from a
    | generated summary. Set POST_SUMMARY_MIN_WORDS in your .env to override.
    |
    */

    'summarise_min_words' => env('POST_SUMMARY_MIN_WORDS', 50),

];
