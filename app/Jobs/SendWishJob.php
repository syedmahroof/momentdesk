<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Services\WishService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWishJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly MessageLog $messageLog) {}

    public function handle(WishService $wishService): void
    {
        $wishService->sendNow($this->messageLog);
    }
}
