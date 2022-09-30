<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPosters;
use App\Services\KodiService;

class ApiController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        return response()->json(['message' => 'Hello.']);
    }

    public function controlDisplay($command)
    {
        $command = strtolower($command);
        $output = 'Invalid command.';

        if ($command === 'on' || $command === 'standby') {
            $output = shell_exec("echo ".$command." 0 | cec-client -s -d 1");
        }

        return response()->json(['message' => $output]);
    }

    /**
     * Re download posters from external service
     *
     * @param PosterService $service
     *
     * @return array
     */
    public function cache()
    {
        SyncPosters::dispatch();

        return ['message' => 'sync job queued'];
    }

    /**
     * Re download posters from external service
     *
     * @param PosterService $service
     *
     * @return array
     */
    public function checkSyncStatus()
    {
        $status = 'clear';
        $jobCount = \DB::table('jobs')->where('payload', 'like', '%SyncPosters%')->count();
        if ($jobCount > 0) {
            $status = 'running';
        }

        return ['status' => $status, 'count' => $jobCount];
    }

    /**
     * Get now playing from Kodi service
     *
     * @param App\Services\KodiService $service
     *
     * @return array
     */
    public function kodiNowPlaying(KodiService $service)
    {
        $nowPlaying = $service->nowPlaying();
        return $nowPlaying;
    }
}
