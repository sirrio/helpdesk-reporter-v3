<?php

namespace App\Jobs;

use App\Models\SystemSetting;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordQueueHeartbeat implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(public readonly ?string $automationCheckToken = null) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'automation-queue-heartbeat';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $settings = SystemSetting::current();
        $updates = ['queue_heartbeat_at' => now()];

        if (
            $this->automationCheckToken !== null
            && $settings->automation_check_token === $this->automationCheckToken
        ) {
            $updates['automation_check_completed_at'] = now();
            $updates['automation_check_completed_token'] = $this->automationCheckToken;
        }

        $settings->forceFill($updates)->save();
    }
}
