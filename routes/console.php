<?php

use App\Models\Issue;
use App\Jobs\EmailDailyIssue;
use App\Jobs\EmailDigestIssue;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('feed:daily {email}', function ($email) {
    $user = User::where('email', $email)->first();

    $digest = \App\Models\Daily::find($user->default_digest_id);

    dispatch(new \App\Jobs\CreateDailyIssue($digest));
});

Artisan::command('feed:subscribe {email} {url}', function ($email, $url) {
    $user = User::firstOrCreate([
       'email'  => $email
    ]);

    $feed = \App\Models\Feed::firstOrCreate([
        'url'    => $url
    ]);

    // subscribe...
    $feed->subscribers()->attach($user, ['daily_id' => $user->default_daily_id]);

    // if this is a new feed record only...
    if($feed->wasRecentlyCreated === true) dispatch(new \App\Jobs\CheckFeed($feed, true));
});

Artisan::command('feed:add {url}', function ($url) {
    $feed = \App\Models\Feed::firstOrCreate([
        'url'    => $url
    ]);

    dispatch(new \App\Jobs\CheckFeed($feed, true));
});

Artisan::command('feed:check', function () {
    $feeds = \App\Models\Feed::get();

    foreach($feeds as $feed)
    {
        dispatch(new \App\Jobs\CheckFeed($feed));
    }
});

Artisan::command('issue:send', function () {
    $user = User::where('email', 'phils@hey.com')->first();

    $issue = Issue::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();

    dispatch( new EmailDailyIssue($issue));
});
