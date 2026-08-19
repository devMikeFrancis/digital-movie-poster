<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * Whether the admin screens ask for a login.
 *
 * The stored setting is the answer, so an operator can change it from Settings
 * without editing .env on the device. DMP_REQUIRE_LOGIN is the fallback for the
 * moments there is nothing to read - before the first migration, or if the
 * settings row has gone - and it seeded the column when this became a setting.
 *
 * Anything unreadable falls back to requiring a login. Failing open would mean
 * a database problem quietly unlocking the device.
 */
class AdminAccess
{
    private static ?bool $cached = null;

    public static function loginRequired(): bool
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        try {
            $settings = Setting::first();

            if ($settings && $settings->getAttribute('require_login') !== null) {
                return self::$cached = (bool) $settings->require_login;
            }
        } catch (Throwable) {
            // No settings table yet, or no database at all.
        }

        return self::$cached = (bool) config('dmp.auth.required');
    }

    /** Forget the resolved value - used when the setting is saved, and in tests. */
    public static function forget(): void
    {
        self::$cached = null;
    }
}
