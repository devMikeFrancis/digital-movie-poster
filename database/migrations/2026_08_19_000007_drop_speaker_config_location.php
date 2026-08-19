<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the speaker badge's location.
 *
 * It could sit at the top right, floating over the header. The top of the
 * screen now carries only the header wording and the theatre name - the runtime
 * moved down with it - so there is one place left for the badge and nothing to
 * choose between.
 *
 * A display set to top-right loses nothing but the position: the badge is still
 * shown, in the footer with the rating and the logos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'speaker_config_location')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('speaker_config_location');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('speaker_config_location', 50)->default('bottom');
        });
    }
};
