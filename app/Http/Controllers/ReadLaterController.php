<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\ReadLater;
use App\Models\Subscription;
use Illuminate\Http\Request;

class ReadLaterController extends Controller
{
    public function index()
    {
        $posts = ReadLater::with('post', 'post.feed')
                            ->where('user_id', auth()->id())
                            ->oldest()
                            ->get();

        if($posts->count() == 0)
        {
            return redirect()->route('home');
        }


        // TODO: Order feeds in a uniform way
        $posts = $posts->groupBy('post.feed_id');

        $posts = $posts->each(function ($item, $index) {
            $subscription = Subscription::where('user_id', auth()->id())
                ->where('feed_id', $item->first()->post->feed_id)
                ->first();

            $item->each(function ($article) use ($subscription) {
                $article->post->feed_title = $subscription->feed_title;
            });
        });

        // Sort by the order feeds were added - eventually a custom sort order
        $posts = collect($posts)->sortKeys();

        return view('readlater.index', [
           'posts' => $posts,
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        ReadLater::query()
            ->where('post_id', $post->id)
            ->where('user_id', auth()->id())
            ->delete();

        return back();
    }
}
