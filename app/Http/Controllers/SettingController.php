<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Http\Resources\PublicSettingResource;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

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

        return response()->json(['saved' => 1]);
    }

    public function updateApplication()
    {
        $success = true;
        $process = new Process(['sh', base_path().'/update.sh']);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            $success = false;
            Log::info(' -- Could not run update script. -- ');
            Log::info(' -- ');
        }

        $output = $process->getOutput();

        Log::info($output);

        return response()->json(['success' => $success, 'output' => $output]);
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
