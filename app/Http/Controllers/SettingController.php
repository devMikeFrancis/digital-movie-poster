<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

    public function update(Request $request)
    {
        $updated = Setting::where('id', 1)->update($request->except('_method', '_token'));

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
