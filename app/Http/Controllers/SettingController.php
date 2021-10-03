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
        $settings = Setting::first();
        $settings->plex_service = $request->boolean('plex_service');
        $settings->random_order = $request->boolean('random_order');
        $settings->plex_ip_address = $request->plex_ip_address;
        $settings->plex_token = $request->plex_token;
        $settings->poster_display_speed = $request->poster_display_speed;
        $settings->poster_display_limit = $request->poster_display_limit;
        $settings->coming_soon_text = $request->coming_soon_text;
        $settings->now_playing_text = $request->now_playing_text;
        $settings->show_mpaa_rating = $request->boolean('show_mpaa_rating');
        $settings->show_audience_rating = $request->boolean('show_audience_rating');
        $settings->show_processing_logos = $request->boolean('show_processing_logos');
        $settings->show_dolby_atmos_horizontal = $request->boolean('show_dolby_atmos_horizontal');
        $settings->show_dolby_atmos_vertical = $request->boolean('show_dolby_atmos_vertical');
        $settings->show_dolby_vision_horizontal = $request->boolean('show_dolby_vision_horizontal');
        $settings->show_dolby_vision_vertical = $request->boolean('show_dolby_vision_vertical');
        $settings->show_dts = $request->boolean('show_dts');
        $settings->show_dolby_51 = $request->boolean('show_dolby_51');
        $settings->show_imax = $request->boolean('show_imax');
        $settings->show_auro_3d = $request->boolean('show_auro_3d');
        $settings->use_cec_power = $request->boolean('use_cec_power');
        $settings->show_runtime = $request->boolean('show_runtime');
        $settings->play_theme_music = $request->boolean('play_theme_music');
        $settings->use_global_prologos = $request->boolean('use_global_prologos');
        $settings->use_global_prologos_if_no_poster_prologos = $request->boolean('use_global_prologos_if_no_poster_prologos');
        $settings->save();

        return response()->json(['success' => true]);
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
