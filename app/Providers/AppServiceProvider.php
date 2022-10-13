<?php

namespace App\Providers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::before(function (JobProcessing $event) {
            $jobName = $event->job->payload()['displayName'];
            if ($jobName === 'App\Jobs\SyncPosters') {
                //
            }
        });

        Queue::after(function (JobProcessed $event) {
            $jobName = $event->job->payload()['displayName'];
            if ($jobName === 'App\Jobs\SyncPosters') {
                //
            }
        });
    }
}
