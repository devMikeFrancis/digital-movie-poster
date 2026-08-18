<?php

namespace Tests\Feature;

use App\Models\Poster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTokenProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_privileged_routes_are_open_when_protection_is_off(): void
    {
        config(['dmp.api.require_token' => false]);

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])->assertOk();
    }

    public function test_privileged_routes_reject_anonymous_callers_when_protection_is_on(): void
    {
        config(['dmp.api.require_token' => true]);

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])->assertUnauthorized();
        $this->putJson('/api/settings', [])->assertUnauthorized();
        $this->getJson('/api/control-display/on')->assertUnauthorized();
        $this->getJson('/api/update-application')->assertUnauthorized();
        $this->getJson('/api/cache-posters')->assertUnauthorized();
        $this->postJson('/api/now-playing', [])->assertUnauthorized();
    }

    public function test_the_display_can_still_read_when_protection_is_on(): void
    {
        config(['dmp.api.require_token' => true]);
        Poster::create(['name' => 'Alien', 'file_name' => 'a.webp', 'media_type' => 'movie']);

        $this->getJson('/api/posters')->assertOk();
        $this->getJson('/api/settings')->assertOk();
        $this->getJson('/api/sync-status')->assertOk();
    }

    public function test_a_valid_token_is_accepted_when_protection_is_on(): void
    {
        config(['dmp.api.require_token' => true]);

        $user = User::create([
            'name' => 'DMP API',
            'email' => 'api@example.test',
            'password' => 'secret',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertOk();
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        config(['dmp.api.require_token' => true]);

        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertUnauthorized();
    }

    public function test_the_token_command_issues_a_usable_token(): void
    {
        config(['dmp.api.require_token' => true]);

        $this->artisan('dmp:token', ['name' => 'integration'])->assertSuccessful();

        $token = PersonalAccessToken::first();
        $this->assertNotNull($token);
        $this->assertSame('integration', $token->name);
    }

    public function test_control_display_rejects_an_unknown_command(): void
    {
        $this->getJson('/api/control-display/explode')->assertStatus(422);
    }
}
