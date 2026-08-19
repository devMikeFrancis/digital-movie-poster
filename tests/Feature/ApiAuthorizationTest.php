<?php

namespace Tests\Feature;

use App\Models\Poster;
use App\Models\Setting;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Privileged endpoints accept either an admin session (the bundled UI) or a
 * Sanctum bearer token (an integration). The endpoints the kiosk display polls
 * stay open, because the display cannot log in.
 */
class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{string, string}> */
    public static function privilegedEndpoints(): array
    {
        return [
            'toggle rotation' => ['postJson', '/api/show-in-rotation'],
            'update settings' => ['putJson', '/api/settings'],
            'read credentials' => ['getJson', '/api/settings/full'],
            'queue a sync' => ['getJson', '/api/cache-posters'],
            'drive the display' => ['getJson', '/api/control-display/on'],
            'broadcast now playing' => ['postJson', '/api/now-playing'],
            'update the application' => ['getJson', '/api/update-application'],
        ];
    }

    /** @return array<int, array{string}> */
    public static function displayEndpoints(): array
    {
        return [
            ['/api/posters'],
            ['/api/settings'],
            ['/api/sync-status'],
            ['/api/auth/status'],
        ];
    }

    #[DataProvider('privilegedEndpoints')]
    public function test_privileged_endpoints_reject_anonymous_callers(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertUnauthorized();
    }

    #[DataProvider('displayEndpoints')]
    public function test_the_display_endpoints_stay_open(string $uri): void
    {
        Poster::create(['name' => 'A', 'file_name' => 'a.webp', 'media_type' => 'movie']);

        $this->getJson($uri)->assertOk();
    }

    public function test_control_display_only_accepts_known_commands(): void
    {
        $this->actingAsAdmin()->getJson('/api/control-display/rm-rf')->assertStatus(422);
    }

    public function test_an_admin_session_is_accepted(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertOk();
    }

    public function test_a_bearer_token_is_accepted(): void
    {
        $token = User::factory()->create()->createToken('integration')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertOk();
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer nonsense')
            ->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertUnauthorized();
    }

    public function test_everything_opens_up_when_login_is_disabled(): void
    {
        // The stored setting is what the middleware reads now; the env value
        // only seeds it.
        Setting::first()->update(['require_login' => false]);
        AdminAccess::forget();

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])->assertOk();
        $this->getJson('/api/settings/full')->assertOk();
    }

    public function test_an_unreachable_plex_reads_as_a_message_not_a_500(): void
    {
        Http::fake(
            fn () => throw new ConnectionException('refused')
        );

        $this->actingAsAdmin()
            ->getJson('/api/service-sections/plex')
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }

    public function test_an_unknown_sync_service_is_rejected(): void
    {
        $this->actingAsAdmin()->getJson('/api/service-sections/nope')->assertNotFound();
    }

    public function test_the_token_command_issues_a_usable_token(): void
    {
        $this->artisan('dmp:token', ['name' => 'integration'])->assertSuccessful();

        $token = PersonalAccessToken::first();

        $this->assertNotNull($token);
        $this->assertSame('integration', $token->name);
    }
}
