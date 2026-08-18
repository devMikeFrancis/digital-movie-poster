<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Keeps the TV in step with the configured hours. This used to be driven from
 * the browser, which meant the endpoint it called had to stay unauthenticated.
 * Requires a cron entry on the host - install.sh adds one:
 *
 *     * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('dmp:display-power')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
