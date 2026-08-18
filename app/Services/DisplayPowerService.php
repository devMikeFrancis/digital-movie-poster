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
     * When the optional PIR sensor last saw someone.
     */
    private const MOTION_KEY = 'dmp.display.last_motion';

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

        $now = $now ? $now->copy() : Carbon::now();

        if (! $this->withinSchedule($settings, $now)) {
            return self::STANDBY;
        }

        // Inside the hours the display may be on; the sensor, if fitted,
        // decides whether it should be right now.
        return $this->presenceDetected($now) ? self::ON : self::STANDBY;
    }

    /**
     * Whether the configured hours currently allow the display to be on.
     */
    public function withinSchedule(Setting $settings, ?CarbonInterface $now = null): bool
    {
        $start = $this->parseTime($settings->start_power_time);
        $end = $this->parseTime($settings->end_power_time);

        if ($start === null || $end === null) {
            return true;
        }

        $now = $now ? $now->copy() : Carbon::now();
        $minutes = ($now->hour * 60) + $now->minute;

        // An end time earlier than the start time means the window runs past
        // midnight, e.g. on at 20:00 and off at 02:00.
        return $start <= $end
            ? ($minutes >= $start && $minutes < $end)
            : ($minutes >= $start || $minutes < $end);
    }

    /**
     * Whether someone appears to be in the room.
     *
     * Always true when no sensor is configured. Also true when a sensor is
     * configured but has never reported - a sensor that is unplugged or
     * miswired should cost the power saving, not the display.
     */
    public function presenceDetected(?CarbonInterface $now = null): bool
    {
        if (! config('dmp.motion.enabled')) {
            return true;
        }

        $last = Cache::get(self::MOTION_KEY);

        if (! $last) {
            return true;
        }

        $now = $now ? $now->copy() : Carbon::now();
        $idleMinutes = max(1, (int) config('dmp.motion.idle_minutes', 5));

        return $now->lessThan(Carbon::parse($last)->addMinutes($idleMinutes));
    }

    /**
     * Note that the sensor has just seen movement.
     */
    public function recordMotion(?CarbonInterface $at = null): void
    {
        Cache::put(
            self::MOTION_KEY,
            ($at ? $at->copy() : Carbon::now())->toIso8601String(),
            Carbon::now()->addDay()
        );
    }

    /**
     * When the sensor last saw movement, if ever.
     */
    public function lastMotionAt(): ?CarbonInterface
    {
        $last = Cache::get(self::MOTION_KEY);

        return $last ? Carbon::parse($last) : null;
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
     * Forget the recorded motion, so presence falls back to "unknown".
     */
    public function forgetMotion(): void
    {
        Cache::forget(self::MOTION_KEY);
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
