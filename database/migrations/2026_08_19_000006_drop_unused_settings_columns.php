<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops five settings columns that nothing reads.
 *
 * - show_header_border drew the box around the header text. The plate styles
 *   replaced it, 2026_08_19_000005 converted every display that had it on to
 *   the plaque style, and the display has read header_style ever since - so the
 *   checkbox on the settings page has been doing nothing since that release.
 * - poster_display_limit was never wired to anything.
 * - The _horizontal logo flags are left over from a time when each format had a
 *   wide and a tall logo. Only the tall ones survived, and the _vertical flags
 *   are what the display reads.
 * - tmdb_api_key_v4 has no field on the settings page and no caller; the v3 key
 *   is what every TMDB request uses.
 *
 * Irreversible on purpose: down() puts the columns back so the schema matches,
 * but there is nothing to restore into them.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const COLUMNS = [
        'show_header_border',
        'poster_display_limit',
        'show_dolby_atmos_horizontal',
        'show_dolby_vision_horizontal',
        'tmdb_api_key_v4',
    ];

    public function up(): void
    {
        $present = array_filter(
            self::COLUMNS,
            fn (string $column) => Schema::hasColumn('settings', $column)
        );

        if ($present === []) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) use ($present) {
            $table->dropColumn(array_values($present));
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('show_header_border')->default(true);
            $table->integer('poster_display_limit')->nullable();
            $table->boolean('show_dolby_atmos_horizontal')->default(false);
            $table->boolean('show_dolby_vision_horizontal')->default(false);
            $table->text('tmdb_api_key_v4')->nullable();
        });
    }
};
