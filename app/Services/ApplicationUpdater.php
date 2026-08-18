<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * Runs the deploy script.
 *
 * Behind a service so it can be swapped out in tests. Calling the endpoint
 * directly from a test executes a real deploy - 'artisan down', 'git pull',
 * 'composer install --no-dev' - which will happily delete the dev
 * dependencies out from under the test runner.
 */
class ApplicationUpdater
{
    /**
     * @return array{success: bool, output: string}
     */
    public function run(): array
    {
        // bash, not sh: update.sh uses [[ ]] and (( )), and /bin/sh is dash
        // on Debian. The PHP binary is passed explicitly because the web
        // server's PATH does not necessarily contain one.
        $process = new Process(['bash', base_path().'/update.sh'], base_path());
        $process->setTimeout(3600);
        $process->setEnv([
            'DMP_PHP' => PHP_BINARY,
            'PATH' => dirname(PHP_BINARY).PATH_SEPARATOR.(getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin'),
        ]);

        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => trim($process->getOutput()."\n".$process->getErrorOutput()),
        ];
    }
}
