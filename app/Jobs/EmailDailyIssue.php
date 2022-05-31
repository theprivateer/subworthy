<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Notifications\NewIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmailDailyIssue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var \App\Models\Issue
     */
    private $issue;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Issue $issue
     */
    public function __construct(Issue $issue)
    {
        $this->issue = $issue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->issue->loadIssue();

        $this->issue->user->notify(new NewIssue($this->issue));
    }
}
