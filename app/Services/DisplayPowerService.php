<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Turns the attached display on and off over HDMI-CEC.
 *
 * This used to be driven from the browser: the kiosk worked out whether it was
 * inside the configured hours and called /api/control-display itself, which
 * meant that endpoint had to stay unauthenticated, and the window was compared
 * against a hardcoded America/New_York clock. It runs from the scheduler now,
 * against the application timezone.
 */
class DisplayPowerService
{
    public const ON = 'on';

    public const STANDBY = 'standby';

    /**
     * Remembers the last command actually sent, so the scheduler can run every
     * minute without spamming the TV with redundant CEC traffic.
     */
    private const STATE_KEY = 'dmp.display.power_state';

    /**
     * Whether the display should be powered on at the given moment.
     *
     * A missing start or end time means "no schedule configured", and the
     * display is left on rather than being switched off indefinitely.
     */
    public function desiredState(?CarbonInterface $now = null): string
    {
        $settings = Setting::first();

        if (! $settings || ! $settings->use_cec_power) {
            return self::ON;
        }

        $start = $this->parseTime($settings->start_power_time);
        $end = $this->parseTime($settings->end_power_time);

        if ($start === null || $end === null) {
            return self::ON;
        }

        $now = $now ? $now->copy() : Carbon::now();
        $minutes = ($now->hour * 60) + $now->minute;

        // An end time earlier than the start time means the window runs past
        // midnight, e.g. on at 20:00 and off at 02:00.
        $within = $start <= $end
            ? ($minutes >= $start && $minutes < $end)
            : ($minutes >= $start || $minutes < $end);

        return $within ? self::ON : self::STANDBY;
    }

    /**
     * Bring the display in line with the schedule.
     *
     * @return string|null the command sent, or null if nothing needed doing
     */
    public function sync(bool $force = false): ?string
    {
        $settings = Setting::first();

        if (! $settings || ! $settings->use_cec_power) {
            return null;
        }

        $desired = $this->desiredState();

        if (! $force && Cache::get(self::STATE_KEY) === $desired) {
            return null;
        }

        $this->send($desired);
        Cache::forever(self::STATE_KEY, $desired);

        return $desired;
    }

    /**
     * Send a single command to the display.
     *
     * The command is piped to cec-client on stdin rather than built into a
     * shell string, so nothing is ever interpolated into a command line.
     *
     * @throws ProcessFailedException
     */
    public function send(string $command): string
    {
        if (! in_array($command, [self::ON, self::STANDBY], true)) {
            throw new \InvalidArgumentException('Unsupported display command: '.$command);
        }

        $process = new Process(['cec-client', '-s', '-d', '1']);
        $process->setInput($command.' 0'.PHP_EOL);
        $process->setTimeout(30);
        $process->mustRun();

        Log::info('Display set to "'.$command.'" over HDMI-CEC.');

        return $process->getOutput();
    }

    /**
     * Forget the cached state, so the next sync sends a command regardless.
     */
    public function forgetState(): void
    {
        Cache::forget(self::STATE_KEY);
    }

    /**
     * Minutes past midnight, or null when the value is not a usable time.
     */
    private function parseTime(?string $time): ?int
    {
        if (! $time) {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }
}
