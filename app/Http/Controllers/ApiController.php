<?php

namespace App\Http\Controllers;

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
}
