<?php

use App\Jobs\CheckFeed;
use App\Jobs\CreateDailyIssue;
use App\Jobs\RemoveUnsubscribedFeeds;
use App\Models\Feed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(function () {
            $time = Carbon::now()->format('Hi');
            $day = Carbon::now()->format('N');

            $feeds = Feed::where('next_check_at', $time)->whereNotNull('next_check_at')->get();

            foreach ($feeds as $feed) {
                dispatch(new CheckFeed($feed, false, config('feeds.refresh_posts', false)));
            }

            $users = User::query()
                ->where('delivery_time', $time)
                ->whereNull('paused')
                ->get();

            foreach ($users as $user) {
                if (strpos($user->days_of_week, $day) !== false) {
                    dispatch(new CreateDailyIssue($user));
                }
            }
        })->everyMinute();

        $schedule->command('model:prune')->daily();

        $schedule->call(function () {
            dispatch(new RemoveUnsubscribedFeeds());
        })->daily();
    })
    ->create();
