<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Http\Resources\PublicSettingResource;
use App\Models\Setting;
use App\Services\ApplicationUpdater;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function __construct() {}

    /**
     * Settings for the kiosk display. Unauthenticated, so credentials are
     * stripped - see PublicSettingResource.
     */
    public function index()
    {
        return new PublicSettingResource(Setting::firstOrFail());
    }

    /**
     * The complete settings row for the admin UI, including credentials.
     * Gated by the same opt-in token as every other privileged endpoint.
     */
    public function full()
    {
        return response()->json(Setting::firstOrFail());
    }

    public function update(SettingsRequest $request)
    {
        $settings = Setting::firstOrFail();
        $settings->fill($request->validated())->save();

        // The middleware asks AdminAccess whether a login is needed, and it
        // remembers the answer for the request - so a change to that setting
        // has to clear it rather than take effect on the next page load.
        AdminAccess::forget();

        return response()->json(['saved' => 1]);
    }

    public function updateApplication(ApplicationUpdater $updater)
    {
        $result = $updater->run();

        if (! $result['success']) {
            Log::warning('Update script failed: '.$result['output']);

            // This used to return 200 with success:false, so the About page
            // took the .then() branch and told the operator the update had
            // completed even when it had refused to run.
            return response()->json([
                'success' => false,
                'message' => 'The update did not run.',
                'output' => $result['output'],
            ], 500);
        }

        Log::info($result['output']);

        return response()->json(['success' => true, 'output' => $result['output']]);
    }

    public function checkUpdate()
    {
        $url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/public/version.json',
            config('dmp.update.repository'),
            config('dmp.update.branch')
        );

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            Log::warning('Update check failed ('.$response->status().'): '.$url);

            return response()->json(['message' => 'Could not reach the update server.'], 503);
        }

        return response()->json($response->json());
    }
}
