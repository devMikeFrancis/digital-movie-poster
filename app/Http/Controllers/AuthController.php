<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Session login for the admin UI.
 *
 * DMP is a single-operator appliance, so there is one account rather than user
 * management. Integrations authenticate with Sanctum tokens instead - see
 * `php artisan dmp:token`.
 */
class AuthController extends Controller
{
    /**
     * What the SPA needs to decide between the login form, the first-run setup
     * form, and letting the user straight through.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'required' => AdminAccess::loginRequired(),
            'authenticated' => $request->user('sanctum') !== null,
            'needs_setup' => ! User::query()->exists() && (bool) config('dmp.auth.allow_setup'),
            'user' => $request->user('sanctum')?->only(['id', 'username']),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember', true))) {
            throw ValidationException::withMessages([
                'username' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'authenticated' => true,
            'user' => $request->user()->only(['id', 'username']),
        ]);
    }

    /**
     * Change the signed-in account's username or password.
     *
     * DMP has one operator account, so this edits your own login and nothing
     * else. The current password is required either way: a left-open browser
     * should not be enough to take the account over.
     */
    public function updateAccount(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $data = $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            // Blank leaves the password alone.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required', 'current_password'],
        ]);

        $user->username = $data['username'];

        $changedPassword = ! empty($data['password']);

        if ($changedPassword) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // Keep this browser signed in, but make the old session id useless.
        $request->session()->regenerate();

        return response()->json([
            'user' => $user->only(['id', 'username']),
            'password_changed' => $changedPassword,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['authenticated' => false]);
    }

    /**
     * Create the admin account on a device that does not have one yet.
     *
     * Only possible while no user exists, so it cannot be used to add a second
     * account or to take over an install that is already set up.
     */
    public function setup(Request $request): JsonResponse
    {
        if (! config('dmp.auth.allow_setup')) {
            return response()->json([
                'message' => 'Setup through the browser is disabled. Use: php artisan dmp:user',
            ], 403);
        }

        if (User::query()->exists()) {
            return response()->json([
                'message' => 'This device already has an account. Sign in instead.',
            ], 409);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'authenticated' => true,
            'user' => $user->only(['id', 'username']),
        ], 201);
    }
}
