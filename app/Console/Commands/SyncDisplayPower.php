<?php

namespace App\Console\Commands;

use App\Services\DisplayPowerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps the display in line with the configured on/off hours.
 *
 * Runs every minute from the scheduler. Only sends a CEC command when the
 * desired state has actually changed, so the TV is not pestered every minute.
 */
class SyncDisplayPower extends Command
{
    protected $signature = 'dmp:display-power
                            {state? : Force "on" or "standby" instead of following the schedule}
                            {--force : Send the command even if the display should already be in that state}';

    protected $description = 'Apply the configured display on/off schedule over HDMI-CEC';

    public function handle(DisplayPowerService $display): int
    {
        $state = $this->argument('state');

        try {
            if ($state !== null) {
                $display->send($state);
                $display->forgetState();
                $this->info('Display set to "'.$state.'".');

                return self::SUCCESS;
            }

            $sent = $display->sync($this->option('force'));
        } catch (Throwable $e) {
            // A Pi with no TV attached, or without cec-utils installed, is not
            // worth failing a scheduled run over.
            Log::warning('Display power sync failed: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($sent === null
            ? 'Display already in the right state; nothing sent.'
            : 'Display set to "'.$sent.'".');

        return self::SUCCESS;
    }
}
