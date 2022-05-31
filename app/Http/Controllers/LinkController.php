<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;

class LinkController extends Controller
{
    public function show(User $user, Post $post)
    {
        // Log activity on the user if it is a free account
        $user->logInteraction();

        // Redirect
        return redirect()->away($post->url);
    }
}
