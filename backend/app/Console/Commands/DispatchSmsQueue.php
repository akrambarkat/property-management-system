<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsJob;
use App\Models\SmsQueue;
use Illuminate\Console\Command;

class DispatchSmsQueue extends Command
{
    protected $signature = 'sms:dispatch';

    protected $description = 'Dispatch pending SMS queue items to the Laravel job queue';

    public function handle(): int
    {
        $items = SmsQueue::where('status', SmsQueue::STATUS_PENDING)
            ->where('available_at', '<=', now())
            ->limit(1000)
            ->get();

        if ($items->isEmpty()) {
            $this->info('لا توجد رسائل SMS في انتظار الإرسال.');
            return self::SUCCESS;
        }

        foreach ($items as $item) {
            SendSmsJob::dispatch($item->id);
        }

        $this->info("تمت إضافة {$items->count()} رسالة إلى قائمة الانتظار.");
        return self::SUCCESS;
    }
}
