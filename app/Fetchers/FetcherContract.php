<?php

namespace App\Fetchers;

use App\Models\Post;

interface FetcherContract
{
    public function fetch(Post $post);
}
