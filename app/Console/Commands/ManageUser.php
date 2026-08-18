<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the admin account, or resets its password. DMP has a single
 * operator account, so running this again updates the existing one.
 */
class ManageUser extends Command
{
    protected $signature = 'dmp:user
                            {--email= : Email address to sign in with}
                            {--name= : Display name}
                            {--password= : Password (prompted for if omitted)}';

    protected $description = 'Create the DMP admin account or reset its password';

    public function handle(): int
    {
        $existing = User::query()->first();

        $email = $this->option('email')
            ?: $this->ask('Email address', $existing?->email ?? 'admin@digital-movie-poster.local');

        $name = $this->option('name')
            ?: $this->ask('Display name', $existing?->name ?? 'Administrator');

        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', Password::min(8)],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($existing) {
            $existing->update([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info('Updated the admin account ('.$email.').');
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info('Created the admin account ('.$email.').');
        }

        if (! config('dmp.auth.required')) {
            $this->newLine();
            $this->warn('DMP_REQUIRE_LOGIN is false, so the admin UI is not asking for this login yet.');
        }

        return self::SUCCESS;
    }
}
