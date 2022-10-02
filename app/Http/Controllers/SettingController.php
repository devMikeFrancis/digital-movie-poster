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
        $settings->show_dolby_51 = $settings->show_dolby_51 ? true : false;
        $settings->show_auro_3d = $settings->show_auro_3d ? true : false;
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
        }

        $output = $process->getOutput();

        return response()->json(['success' => $success, 'output' => $output]);
    }
}
