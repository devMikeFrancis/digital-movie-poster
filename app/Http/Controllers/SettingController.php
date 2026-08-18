<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SettingController extends Controller
{
    public function __construct() {}

    public function index()
    {
        $settings = Setting::first();

        return response()->json($settings);
    }

    public function update(SettingsRequest $request)
    {
        $updated = Setting::where('id', 1)->update($request->validated());

        return response()->json(['saved' => $updated]);
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
