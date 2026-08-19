<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sign in with a username instead of an email address.
 *
 * DMP has one operator account on a device that sends no mail: there is no
 * mailer configured, no password reset and no verification, so the email was
 * only ever a login name that had to look like an address - and it implied an
 * account recovery that does not exist. The display name went with it; on a
 * single-account appliance the username is the label.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Each step is guarded: on SQLite a column change rebuilds the table,
        // so a half-applied run has to be safe to repeat.
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('id');
            });
        }

        // Carry existing accounts across: the part before the @ is the closest
        // thing to a username they already had.
        foreach (DB::table('users')->whereNull('username')->get() as $user) {
            $candidate = Str::of((string) ($user->email ?? ''))
                ->before('@')
                ->trim()
                ->value();

            if ($candidate === '') {
                $candidate = 'admin';
            }

            $username = $candidate;
            $suffix = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $candidate.(++$suffix);
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        $indexes = collect(Schema::getIndexes('users'))->pluck('name');

        if (! $indexes->contains('users_username_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        }

        // SQLite refuses to drop a column an index still refers to, so the
        // email unique index has to go first.
        DB::statement('DROP INDEX IF EXISTS users_email_unique');

        Schema::table('users', function (Blueprint $table) {
            foreach (['email', 'email_verified_at', 'name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Only ever existed to support emailed password resets.
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });

        foreach (DB::table('users')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => $user->username,
                'email' => $user->username.'@digital-movie-poster.local',
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
