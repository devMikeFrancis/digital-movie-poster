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
        // Run it with bash, not sh: the script uses [[ ]] and (( )), which
        // dash - /bin/sh on Debian - cannot parse.
        $process = new Process(['bash', base_path().'/update.sh'], base_path());
        $process->setTimeout(3600);

        // The web server's PATH does not necessarily include the PHP binary
        // that is serving this request, so hand it over explicitly rather than
        // letting the script guess.
        $process->setEnv([
            'DMP_PHP' => PHP_BINARY,
            'PATH' => dirname(PHP_BINARY).PATH_SEPARATOR.(getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin'),
        ]);

        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            Log::warning('Update script failed: '.$output);

            // This used to return 200 with success:false, so the About page
            // took the .then() branch and told the operator the update had
            // completed even when it had refused to run.
            return response()->json([
                'success' => false,
                'message' => 'The update did not run.',
                'output' => $output,
            ], 500);
        }

        Log::info($output);

        return response()->json(['success' => true, 'output' => $output]);
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
