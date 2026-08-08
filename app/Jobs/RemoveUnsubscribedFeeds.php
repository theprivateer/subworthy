<?php

namespace App\Jobs;

use App\Models\Feed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::error('RemoveUnsubscribedFeeds failed', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
