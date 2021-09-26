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
        shell_exec("echo '".$command." 0' | cec-client -s -d 1");
    }
}
