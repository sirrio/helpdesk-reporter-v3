<?php

namespace App\Console\Commands;

use App\Jobs\RecordQueueHeartbeat;
use App\Models\SystemSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('automation:heartbeat')]
#[Description('Record the scheduler heartbeat and dispatch a queue heartbeat job')]
class RecordAutomationHeartbeat extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = SystemSetting::current();
        $settings->forceFill(['scheduler_heartbeat_at' => now()])->save();

        RecordQueueHeartbeat::dispatch($settings->automation_check_token);

        return self::SUCCESS;
    }
}
