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

];
