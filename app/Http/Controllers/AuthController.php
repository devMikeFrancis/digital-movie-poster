<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'required' => (bool) config('dmp.auth.required'),
            'authenticated' => $request->user('sanctum') !== null,
            'needs_setup' => ! User::query()->exists() && (bool) config('dmp.auth.allow_setup'),
            'user' => $request->user('sanctum')?->only(['id', 'name', 'email']),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember', true))) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'authenticated' => true,
            'user' => $request->user()->only(['id', 'name', 'email']),
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'authenticated' => true,
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }
}
