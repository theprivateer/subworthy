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

];
