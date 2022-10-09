<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use Symfony\Component\Process\Process;
use App\Models\Setting;

class SettingController extends Controller
{
    public function __construct()
    {
    }

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

        if (!$process->isSuccessful()) {
            $success = false;
            \Log::info(' -- Could not run update script. -- ');
            \Log::info(' -- ');
        }

        $output = $process->getOutput();

        \Log::info($output);

        return response()->json(['success' => $success, 'output' => $output]);
    }

    public function checkUpdate()
    {
        $file = file_get_contents('https://raw.githubusercontent.com/newelement/digital-movie-poster/main/public/version.json');

        return json_decode($file);
    }
}
