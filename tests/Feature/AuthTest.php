<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $password = 'correct-horse'): User
    {
        return User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make($password),
        ]);
    }

    public function test_status_reports_that_setup_is_needed_on_a_fresh_device(): void
    {
        $this->getJson('/api/auth/status')
            ->assertOk()
            ->assertJson([
                'required' => true,
                'authenticated' => false,
                'needs_setup' => true,
            ]);
    }

    public function test_status_stops_asking_for_setup_once_an_account_exists(): void
    {
        $this->admin();

        $this->getJson('/api/auth/status')
            ->assertOk()
            ->assertJson(['needs_setup' => false, 'authenticated' => false]);
    }

    public function test_the_first_account_can_be_created_and_is_signed_in(): void
    {
        $this->postJson('/api/auth/setup', [
            'username' => 'mike',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertCreated()->assertJson(['authenticated' => true]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['username' => 'mike']);
    }

    public function test_setup_is_refused_once_an_account_exists(): void
    {
        $this->admin();

        $this->postJson('/api/auth/setup', [
            'username' => 'interloper',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertStatus(409);

        $this->assertSame(1, User::count());
    }

    public function test_setup_can_be_disabled_entirely(): void
    {
        config(['dmp.auth.allow_setup' => false]);

        $this->postJson('/api/auth/setup', [
            'username' => 'mike',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertStatus(403);

        $this->getJson('/api/auth/status')->assertJson(['needs_setup' => false]);
    }

    public function test_setup_requires_a_confirmed_password_of_reasonable_length(): void
    {
        $this->postJson('/api/auth/setup', [
            'username' => 'mike',
            'password' => 'short',
            'password_confirmation' => 'nope',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_a_correct_password_signs_in(): void
    {
        $this->admin();

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'correct-horse',
        ])->assertOk()->assertJson(['authenticated' => true]);

        $this->assertAuthenticated();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->admin();

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        $this->assertGuest();
    }

    public function test_signing_in_unlocks_the_privileged_endpoints(): void
    {
        $this->admin();

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertUnauthorized();

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'correct-horse',
        ])->assertOk();

        $this->postJson('/api/show-in-rotation', ['all_show_in_rotation' => true])
            ->assertOk();
    }

    public function test_logging_out_locks_them_again(): void
    {
        $this->admin();
        $this->actingAs(User::first());

        $this->postJson('/api/auth/logout')->assertOk()->assertJson(['authenticated' => false]);

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $this->admin();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'admin@example.test',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'correct-horse',
        ])->assertStatus(429);
    }

    public function test_the_artisan_command_creates_then_updates_the_single_account(): void
    {
        $this->artisan('dmp:user', [
            '--username' => 'operator',
            '--password' => 'a-good-password',
        ])->assertSuccessful();

        $this->assertSame(1, User::count());

        $this->artisan('dmp:user', [
            '--username' => 'operator',
            '--password' => 'a-different-password',
        ])->assertSuccessful();

        $this->assertSame(1, User::count(), 'dmp:user should update, not add a second account');
        $this->assertTrue(Hash::check('a-different-password', User::first()->password));
    }

    public function test_a_username_must_be_a_sensible_identifier(): void
    {
        foreach (['ab', 'has spaces', 'has@symbol', ''] as $bad) {
            $this->postJson('/api/auth/setup', [
                'username' => $bad,
                'password' => 'a-good-password',
                'password_confirmation' => 'a-good-password',
            ])->assertStatus(422)->assertJsonValidationErrors('username');
        }

        $this->assertSame(0, User::count());
    }

    public function test_a_username_can_contain_dashes_and_underscores(): void
    {
        $this->postJson('/api/auth/setup', [
            'username' => 'movie_poster-admin',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['username' => 'movie_poster-admin']);
    }

    public function test_the_account_never_carries_an_email(): void
    {
        // The device sends no mail, so there is nothing for an address to do.
        $this->admin();

        $this->assertNotContains('email', Schema::getColumnListing('users'));
        $this->assertFalse(Schema::hasTable('password_resets'));

        $body = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'correct-horse',
        ])->assertOk()->getContent();

        $this->assertStringNotContainsString('email', $body);
    }

    public function test_the_artisan_command_rejects_a_short_password(): void
    {
        $this->artisan('dmp:user', [
            '--username' => 'operator',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }
}
