<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;

class LinkController extends Controller
{
    public function show(User $user, Post $post)
    {
        // This route is public and both bindings come straight from the URL, so nothing here
        // has been established as related. Requiring the user to actually subscribe to the
        // post's feed stops anyone pairing an arbitrary user with an arbitrary post.
        abort_unless(
            $user->subscriptions()->where('feed_id', $post->feed_id)->exists(),
            404
        );

        // The destination is whatever the feed published, so a publisher could otherwise use
        // this domain as a redirect to anywhere — including javascript: in older clients.
        $destination = $post->safe_url;

        abort_if($destination === null, 404);

        // Log activity on the user if it is a free account
        $user->logInteraction();

        // Redirect
        return redirect()->away($destination);
    }
}
