<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the admin account, or resets its password. DMP has a single
 * operator account, so running this again updates the existing one.
 */
class ManageUser extends Command
{
    protected $signature = 'dmp:user
                            {--username= : Username to sign in with}
                            {--password= : Password (prompted for if omitted)}';

    protected $description = 'Create the DMP admin account or reset its password';

    public function handle(): int
    {
        $existing = User::query()->first();

        $username = $this->option('username')
            ?: $this->ask('Username', $existing?->username ?? 'admin');

        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(
            ['username' => $username, 'password' => $password],
            [
                'username' => [
                    'required', 'string', 'min:3', 'max:255', 'alpha_dash',
                    Rule::unique('users', 'username')->ignore($existing?->id),
                ],
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
                'username' => $username,
                'password' => Hash::make($password),
            ]);

            $this->info('Updated the admin account ('.$username.').');
        } else {
            User::create([
                'username' => $username,
                'password' => Hash::make($password),
            ]);

            $this->info('Created the admin account ('.$username.').');
        }

        if (! AdminAccess::loginRequired()) {
            $this->newLine();
            $this->warn('The admin UI is not asking for a login yet - see Settings, or DMP_REQUIRE_LOGIN.');
        }

        return self::SUCCESS;
    }
}
