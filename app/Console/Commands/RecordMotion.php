<?php

namespace App\Console\Commands;

use App\Services\DisplayPowerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reports movement from the optional PIR sensor.
 *
 * The sensor script calls this instead of driving cec-client itself, so the
 * schedule and the sensor cannot disagree about the display's power state -
 * DisplayPowerService reconciles them in one place.
 */
class RecordMotion extends Command
{
    protected $signature = 'dmp:motion
                            {--status : Report presence without recording movement}';

    protected $description = 'Tell DMP the motion sensor has seen someone';

    public function handle(DisplayPowerService $display): int
    {
        if ($this->option('status')) {
            $last = $display->lastMotionAt();

            $this->line('Sensor: '.(config('dmp.motion.enabled') ? 'enabled' : 'disabled'));
            $this->line('Last motion: '.($last ? $last->diffForHumans() : 'never'));
            $this->line('Presence: '.($display->presenceDetected() ? 'yes' : 'no'));
            $this->line('Display should be: '.$display->desiredState());

            return self::SUCCESS;
        }

        if (! config('dmp.motion.enabled')) {
            $this->warn('Motion sensor is disabled; set DMP_MOTION_SENSOR=true in .env.');

            return self::SUCCESS;
        }

        $display->recordMotion();

        try {
            // Wake the display straight away rather than waiting for the next
            // scheduled run. Does nothing if it is already in the right state.
            $sent = $display->sync();
        } catch (Throwable $e) {
            Log::warning('Motion wake failed: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($sent === null ? 'Motion recorded.' : 'Motion recorded; display set to "'.$sent.'".');

        return self::SUCCESS;
    }
}
