<?php

namespace App\Jobs;

use App\Models\Feed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveUnsubscribedFeeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        Feed::whereDoesntHave('subscribers')->delete();
    }

    public function failed(?\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('RemoveUnsubscribedFeeds failed', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
