<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPosters;
use App\Services\KodiService;
use Illuminate\Http\Request;
use App\Events\DmpEvent;

class ApiController extends Controller
{
    private $validEvents = [
        'now-playing',
        'stopped'
    ];

    private $validMediaTypes = ['movie', 'tv', 'show'];
    private $validMovieRatings = ['G', 'PG', 'PG-13', 'R', 'NC-17', 'NR', 'NOT RATED'];
    private $validTvRatings = ['TV-Y','TV-Y7','TV-Y7 FV','TV-G','TV-PG','TV-14','TV-MA'];

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

    /**
     * DMP websocket broadcaster
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function dmpBroadcast(Request $request)
    {
        $data = $request->all();
        $errors = [];

        if (!isset($data['event'])) {
            $errors[] = 'The event property is required.';
            return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
        }

        if (!in_array($data['event'], $this->validEvents)) {
            $errors[] = 'The event property is not valid. Please check the valid event types.';
            return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
        }

        // Validate the now-playing payload
        if ($data['event'] === 'now-playing') {
            if (!isset($data['mediaType'])) {
                $errors[] = 'The mediaType property is required.';
                return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
            }
            if (!in_array(strtolower($data['mediaType']), $this->validMediaTypes)) {
                $errors[] = 'The mediaType property is not valid. Please use movie,tv or show.';
            }

            if (!isset($data['poster'])) {
                $errors[] = 'The poster property is required.';
                return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
            }

            if ($data['poster'] === '') {
                $errors[] = 'The poster property is required.';
                return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
            }

            if (!isset($data['contentRating']) || $data['contentRating'] === '') {
                $data['contentRating'] = 0;
            }
            if (!isset($data['audienceRating']) || $data['audienceRating'] === '') {
                $data['audienceRating'] = 0;
            }
            if (!isset($data['duration']) || $data['duration'] === '') {
                $data['duration'] = 0;
            }

            if (!is_numeric($data['audienceRating'])) {
                $errors[] = 'The rating must be a numeric value between 1 and 10.';
            }

            if (!is_numeric($data['duration'])) {
                $errors[] = 'The duration must be a numeric value in minutes.';
            }

            if ($data['contentRating'] && $data['contentRating'] !== '') {
                if ($data['mediaType'] === 'movie' && !in_array(strtoupper($data['mediaType']), $this->validMovieRatings)) {
                    $errors[] = 'The movie contentRating is invalid.';
                }

                if (($data['mediaType'] === 'tv' || $data['mediaType'] === 'show') && !in_array(strtoupper($data['mediaType']), $this->validTvRatings)) {
                    $errors[] = 'The tv show contentRating is invalid.';
                }
            }
        }

        if (count($errors) > 0) {
            return response()->json(['success' => false, 'message' => implode(', ', $errors)], 400);
        }

        event(new DmpEvent($data));

        return response()->json(['success' => true, 'message' => 'Event sent']);
    }
}
