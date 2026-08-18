<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\DisplayPowerService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The display on/off schedule. This used to run in the kiosk browser, which
 * forced the endpoint it called to stay unauthenticated and compared the
 * window against a hardcoded America/New_York clock.
 */
class DisplayPowerTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(string $start, string $end, bool $enabled = true): void
    {
        Setting::firstOrFail()->forceFill([
            'use_cec_power' => $enabled,
            'start_power_time' => $start,
            'end_power_time' => $end,
        ])->save();
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function windows(): array
    {
        return [
            'inside a daytime window' => ['09:00:00', '23:00:00', '2026-08-18 12:00:00', 'on'],
            'before a daytime window' => ['09:00:00', '23:00:00', '2026-08-18 08:59:00', 'standby'],
            'after a daytime window' => ['09:00:00', '23:00:00', '2026-08-18 23:30:00', 'standby'],
            'exactly at the start' => ['09:00:00', '23:00:00', '2026-08-18 09:00:00', 'on'],
            'exactly at the end' => ['09:00:00', '23:00:00', '2026-08-18 23:00:00', 'standby'],
            'inside an overnight window' => ['20:00:00', '02:00:00', '2026-08-18 23:30:00', 'on'],
            'after midnight, still inside' => ['20:00:00', '02:00:00', '2026-08-18 01:00:00', 'on'],
            'outside an overnight window' => ['20:00:00', '02:00:00', '2026-08-18 12:00:00', 'standby'],
        ];
    }

    #[DataProvider('windows')]
    public function test_it_works_out_the_right_state(string $start, string $end, string $now, string $expected): void
    {
        $this->schedule($start, $end);

        $this->assertSame(
            $expected,
            app(DisplayPowerService::class)->desiredState(Carbon::parse($now))
        );
    }

    public function test_the_window_follows_the_application_timezone(): void
    {
        $this->schedule('09:00:00', '17:00:00');

        // 14:00 UTC is 09:00 in New York: inside the window there, outside in Tokyo.
        $at = Carbon::parse('2026-08-18 14:00:00', 'UTC');

        config(['app.timezone' => 'America/New_York']);
        $this->assertSame('on', app(DisplayPowerService::class)->desiredState($at->copy()->setTimezone('America/New_York')));

        config(['app.timezone' => 'Asia/Tokyo']);
        $this->assertSame('standby', app(DisplayPowerService::class)->desiredState($at->copy()->setTimezone('Asia/Tokyo')));
    }

    public function test_it_leaves_the_display_on_when_cec_control_is_off(): void
    {
        $this->schedule('09:00:00', '17:00:00', enabled: false);

        $this->assertSame('on', app(DisplayPowerService::class)->desiredState(Carbon::parse('2026-08-18 03:00:00')));
    }

    #[DataProvider('unusableTimes')]
    public function test_an_unusable_schedule_leaves_the_display_on(?string $start, ?string $end): void
    {
        $this->schedule((string) $start, (string) $end);

        $this->assertSame('on', app(DisplayPowerService::class)->desiredState(Carbon::parse('2026-08-18 03:00:00')));
    }

    /** @return array<int, array{?string, ?string}> */
    public static function unusableTimes(): array
    {
        return [
            [null, null],
            ['', ''],
            ['09:00:00', ''],
            ['not a time', '23:00:00'],
            ['25:00:00', '23:00:00'],
        ];
    }

    public function test_sync_does_nothing_when_cec_control_is_off(): void
    {
        $this->schedule('09:00:00', '17:00:00', enabled: false);

        // send() would shell out to cec-client, which is not installed here;
        // reaching it would throw rather than return null.
        $this->assertNull(app(DisplayPowerService::class)->sync());
    }

    public function test_the_command_reports_success_when_nothing_needs_sending(): void
    {
        $this->schedule('09:00:00', '17:00:00', enabled: false);

        $this->artisan('dmp:display-power')
            ->expectsOutputToContain('nothing sent')
            ->assertSuccessful();
    }

    public function test_the_command_fails_cleanly_without_cec_client(): void
    {
        $this->schedule('00:00:00', '23:59:00');
        app(DisplayPowerService::class)->forgetState();

        // cec-client is absent in CI, so this exercises the failure path.
        $this->artisan('dmp:display-power')->assertFailed();
    }

    public function test_the_schedule_is_registered_to_run_every_minute(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'dmp:display-power'));

        $this->assertCount(1, $events, 'dmp:display-power should be scheduled exactly once');
        $this->assertSame('* * * * *', $events->first()->expression);
    }

    public function test_an_unsupported_command_is_refused_before_shelling_out(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(DisplayPowerService::class)->send('rm -rf /');
    }
}
