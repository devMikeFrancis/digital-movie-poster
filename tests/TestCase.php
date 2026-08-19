<?php

namespace Tests;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only starts a session for requests it can tell came from the
        // app's own frontend, which it decides from Referer/Origin. Browsers
        // send those on same-origin requests; the test client does not, so
        // without this every session-authenticated test would 500 on a missing
        // session store.
        $this->withHeader('Referer', config('app.url'));

        // Whether a login is needed is resolved once and remembered for the
        // request. Tests share a process, so it has to be forgotten between
        // them or the first answer decides every later one.
        AdminAccess::forget();
    }

    /**
     * Sign in as the single admin account.
     *
     * Privileged endpoints require a session or a token by default, so tests
     * that are not themselves about authorisation start from a signed-in state.
     */
    protected function actingAsAdmin(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
