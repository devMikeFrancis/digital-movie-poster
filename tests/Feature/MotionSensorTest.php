<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\DisplayPowerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The optional PIR sensor.
 *
 * It used to drive cec-client directly from a Python script while the on/off
 * schedule drove it from the browser, so the two fought: the sensor blanked
 * the screen and the schedule switched it back on within the minute. Presence
 * is now one input to DisplayPowerService, which owns the decision.
 */
class MotionSensorTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(string $start = '00:00:00', string $end = '23:59:00'): void
    {
        Setting::firstOrFail()->forceFill([
            'use_cec_power' => true,
            'start_power_time' => $start,
            'end_power_time' => $end,
        ])->save();
    }

    private function service(): DisplayPowerService
    {
        return app(DisplayPowerService::class);
    }

    public function test_presence_is_assumed_when_no_sensor_is_configured(): void
    {
        config(['dmp.motion.enabled' => false]);
        $this->schedule();

        $this->assertTrue($this->service()->presenceDetected());
        $this->assertSame('on', $this->service()->desiredState());
    }

    public function test_a_sensor_that_has_never_reported_leaves_the_display_on(): void
    {
        // A miswired or unplugged sensor should cost the power saving, not the
        // display.
        config(['dmp.motion.enabled' => true]);
        $this->schedule();
        $this->service()->forgetMotion();

        $this->assertTrue($this->service()->presenceDetected());
        $this->assertSame('on', $this->service()->desiredState());
    }

    public function test_recent_motion_counts_as_presence(): void
    {
        config(['dmp.motion.enabled' => true, 'dmp.motion.idle_minutes' => 5]);
        $this->schedule();

        $this->service()->recordMotion(Carbon::now()->subMinutes(2));

        $this->assertTrue($this->service()->presenceDetected());
        $this->assertSame('on', $this->service()->desiredState());
    }

    public function test_an_empty_room_blanks_the_display(): void
    {
        config(['dmp.motion.enabled' => true, 'dmp.motion.idle_minutes' => 5]);
        $this->schedule();

        $this->service()->recordMotion(Carbon::now()->subMinutes(6));

        $this->assertFalse($this->service()->presenceDetected());
        $this->assertSame('standby', $this->service()->desiredState());
    }

    public function test_the_idle_timeout_is_configurable(): void
    {
        config(['dmp.motion.enabled' => true, 'dmp.motion.idle_minutes' => 30]);
        $this->schedule();

        $this->service()->recordMotion(Carbon::now()->subMinutes(20));

        $this->assertTrue($this->service()->presenceDetected());
    }

    /**
     * The heart of the old conflict: motion must not be able to switch the
     * display on outside the configured hours.
     */
    public function test_motion_cannot_override_the_schedule(): void
    {
        config(['dmp.motion.enabled' => true, 'dmp.motion.idle_minutes' => 5]);
        $this->schedule('09:00:00', '17:00:00');

        $middleOfTheNight = Carbon::parse('2026-08-18 03:00:00');
        $this->service()->recordMotion($middleOfTheNight);

        $this->assertSame('standby', $this->service()->desiredState($middleOfTheNight));
    }

    public function test_the_sensor_only_narrows_the_window(): void
    {
        config(['dmp.motion.enabled' => true, 'dmp.motion.idle_minutes' => 5]);
        $this->schedule('09:00:00', '17:00:00');

        $noon = Carbon::parse('2026-08-18 12:00:00');

        $this->service()->recordMotion($noon->copy()->subMinute());
        $this->assertSame('on', $this->service()->desiredState($noon));

        $this->service()->recordMotion($noon->copy()->subMinutes(10));
        $this->assertSame('standby', $this->service()->desiredState($noon));
    }

    public function test_the_command_records_motion(): void
    {
        config(['dmp.motion.enabled' => true]);
        $this->schedule();
        $this->service()->forgetMotion();

        $this->assertNull($this->service()->lastMotionAt());

        // sync() is a no-op here because cec-client is absent and the cached
        // state already matches, so this exercises the recording path.
        $this->artisan('dmp:motion')->run();

        $this->assertNotNull($this->service()->lastMotionAt());
    }

    public function test_the_command_declines_when_the_sensor_is_disabled(): void
    {
        config(['dmp.motion.enabled' => false]);
        $this->service()->forgetMotion();

        $this->artisan('dmp:motion')
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        $this->assertNull($this->service()->lastMotionAt());
    }

    public function test_the_status_flag_reports_without_recording(): void
    {
        config(['dmp.motion.enabled' => true]);
        $this->schedule();
        $this->service()->forgetMotion();

        $this->artisan('dmp:motion', ['--status' => true])
            ->expectsOutputToContain('Last motion: never')
            ->assertSuccessful();

        $this->assertNull($this->service()->lastMotionAt());
    }
}
