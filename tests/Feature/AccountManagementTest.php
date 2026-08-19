<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Editing your own login from the settings page, so a password change does not
 * require console access to the device.
 */
class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function signedIn(string $password = 'correct-horse'): User
    {
        $user = User::factory()->create([
            'username' => 'operator',
            'password' => Hash::make($password),
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_the_username_can_be_changed(): void
    {
        $user = $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'newname',
            'current_password' => 'correct-horse',
        ])->assertOk()->assertJsonPath('user.username', 'newname');

        $this->assertSame('newname', $user->fresh()->username);
    }

    public function test_the_password_can_be_changed(): void
    {
        $user = $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'operator',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
            'current_password' => 'correct-horse',
        ])->assertOk()->assertJsonPath('password_changed', true);

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_leaving_the_password_blank_keeps_the_existing_one(): void
    {
        $user = $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'renamed',
            'password' => '',
            'current_password' => 'correct-horse',
        ])->assertOk()->assertJsonPath('password_changed', false);

        $this->assertTrue(Hash::check('correct-horse', $user->fresh()->password));
        $this->assertSame('renamed', $user->fresh()->username);
    }

    public function test_the_current_password_is_required(): void
    {
        $user = $this->signedIn();

        $this->putJson('/api/auth/account', ['username' => 'newname'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertSame('operator', $user->fresh()->username);
    }

    public function test_a_wrong_current_password_changes_nothing(): void
    {
        $user = $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'newname',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
            'current_password' => 'not-the-password',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertSame('operator', $user->fresh()->username);
        $this->assertTrue(Hash::check('correct-horse', $user->fresh()->password));
    }

    public function test_a_new_password_must_be_confirmed_and_long_enough(): void
    {
        $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'operator',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'current_password' => 'correct-horse',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_the_username_must_still_be_a_sensible_identifier(): void
    {
        $this->signedIn();

        foreach (['ab', 'has spaces', 'has@symbol'] as $bad) {
            $this->putJson('/api/auth/account', [
                'username' => $bad,
                'current_password' => 'correct-horse',
            ])->assertStatus(422)->assertJsonValidationErrors('username');
        }
    }

    public function test_keeping_your_own_username_is_not_a_uniqueness_clash(): void
    {
        $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'operator',
            'current_password' => 'correct-horse',
        ])->assertOk();
    }

    public function test_the_endpoint_requires_being_signed_in(): void
    {
        User::factory()->create(['username' => 'operator']);

        $this->putJson('/api/auth/account', [
            'username' => 'newname',
            'current_password' => 'correct-horse',
        ])->assertUnauthorized();
    }

    public function test_the_session_survives_a_password_change(): void
    {
        $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'operator',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
            'current_password' => 'correct-horse',
        ])->assertOk();

        // Still signed in: changing your own password should not lock you out
        // of the browser you changed it from.
        $this->getJson('/api/settings/full')->assertOk();
    }

    public function test_the_new_password_works_on_the_next_sign_in(): void
    {
        $this->signedIn();

        $this->putJson('/api/auth/account', [
            'username' => 'renamed',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
            'current_password' => 'correct-horse',
        ])->assertOk();

        $this->postJson('/api/auth/logout')->assertOk();

        $this->postJson('/api/auth/login', [
            'username' => 'renamed',
            'password' => 'a-brand-new-password',
        ])->assertOk()->assertJsonPath('authenticated', true);
    }
}
