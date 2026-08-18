<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class IssueApiToken extends Command
{
    protected $signature = 'dmp:token {name : A label so you can revoke this token later}';

    protected $description = 'Issue a Sanctum API token for driving the DMP API';

    public function handle(): int
    {
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'DMP API',
                'email' => 'api@digital-movie-poster.local',
                'password' => bcrypt(Str::random(40)),
            ]);

            $this->line('Created the API user (no password login - tokens only).');
        }

        $token = $user->createToken($this->argument('name'))->plainTextToken;

        $this->newLine();
        $this->info('API token issued. Copy it now - it will not be shown again.');
        $this->newLine();
        $this->line($token);
        $this->newLine();
        $this->line('Send it as:  Authorization: Bearer '.Str::limit($token, 12, '...'));
        $this->line('Enable enforcement by setting DMP_API_REQUIRE_TOKEN=true in .env');
        $this->newLine();

        return self::SUCCESS;
    }
}
