<?php

namespace App\Interfaces;

interface MovieSyncInterface
{
    public function apiCall(string $path, string $method, array $params);
    public function syncMedia();
    public function setSettings();
}
