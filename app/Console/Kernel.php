<?php

namespace App\Console;

use App\Jobs\RemoveUnsubscribedFeeds;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Feed;
use App\Jobs\CheckFeed;
use App\Jobs\CreateDailyIssue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $time = Carbon::now()->format('Hi');
            $day = Carbon::now()->format('N');

            $feeds = Feed::where('next_check_at', $time)->whereNotNull('next_check_at')->get();

            // Check the feeds
            if($feeds)
            {
                foreach($feeds as $feed)
                {
                    dispatch(new CheckFeed($feed, false, env('REFRESH_POSTS', false)));
                }
            }

            $users = User::query()
                        ->where('delivery_time', $time)
                        ->whereNull('paused')
                        ->get();

            if($users)
            {
                foreach($users as $user)
                {
                    if(strpos($user->days_of_week, $day) !== false)
                    {
                        dispatch(new CreateDailyIssue($user));
                    }
                }
            }
        })->everyMinute();

        $schedule->command('model:prune')->daily();

        // Daily housekeeping
        $schedule->call(function () {
            dispatch(new RemoveUnsubscribedFeeds());
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
