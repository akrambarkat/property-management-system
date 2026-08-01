<?php

namespace App\Jobs;

use App\Models\SmsQueue;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $smsQueueId)
    {
    }

    public function handle(SmsService $sms): void
    {
        $item = SmsQueue::find($this->smsQueueId);
        if (!$item || $item->status === SmsQueue::STATUS_CANCELLED) {
            return;
        }

        $sms->processQueueItem($item);
    }

    public function failed(\Throwable $e): void
    {
        $item = SmsQueue::find($this->smsQueueId);
        if (!$item) {
            return;
        }
        $item->update(['status' => SmsQueue::STATUS_FAILED]);
    }
}
