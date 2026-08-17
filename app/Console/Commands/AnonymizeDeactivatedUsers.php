<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('users:anonymize-deactivated')]
#[Description('Anonymize deactivated users after the configured retention period')]
class AnonymizeDeactivatedUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = SystemSetting::current();
        $cutoff = now()->subMonthsNoOverflow($settings->user_anonymization_months);
        $anonymizedUsers = 0;

        User::onlyTrashed()
            ->whereNull('anonymized_at')
            ->where('deleted_at', '<=', $cutoff)
            ->chunkById(100, function (Collection $users) use (&$anonymizedUsers): void {
                DB::transaction(function () use ($users, &$anonymizedUsers): void {
                    if (config('session.driver') === 'database') {
                        DB::connection(config('session.connection'))
                            ->table(config('session.table'))
                            ->whereIn('user_id', $users->modelKeys())
                            ->delete();
                    }

                    foreach ($users as $user) {
                        $user->anonymize();
                        $anonymizedUsers++;
                    }
                });
            });

        $this->info("{$anonymizedUsers} Benutzer anonymisiert.");

        return self::SUCCESS;
    }
}
