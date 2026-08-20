<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('automation:heartbeat')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('users:anonymize-deactivated')
    ->hourlyAt(15)
    ->withoutOverlapping(60);
