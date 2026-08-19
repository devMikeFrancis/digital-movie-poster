<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Let the artwork cover the whole screen instead of sitting in a
            // 2:3 box between the header and footer.
            $table->boolean('poster_fill_screen')->default(false);

            // "Coming Soon" / "Now Playing" is not wanted on every display.
            $table->boolean('show_header_text')->default(true);

            // The theatre's own name, above or below the poster.
            $table->boolean('show_theater_name')->default(false);
            $table->string('theater_name')->nullable();
            $table->string('theater_name_position', 10)->default('bottom');
        });

        // show_bottom_text / bottom_text were on the model and validated on
        // save but never rendered anywhere, so whatever an operator typed there
        // has never been seen. Carry it over rather than making them type it
        // again, then drop the pair.
        if (Schema::hasColumn('settings', 'bottom_text')) {
            DB::table('settings')->update([
                'theater_name' => DB::raw('bottom_text'),
                'show_theater_name' => DB::raw('COALESCE(show_bottom_text, 0)'),
            ]);
        }

        Schema::table('settings', function (Blueprint $table) {
            foreach (['show_bottom_text', 'bottom_text'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('show_bottom_text')->nullable()->default(false);
            $table->string('bottom_text')->nullable();
        });

        DB::table('settings')->update([
            'bottom_text' => DB::raw('theater_name'),
            'show_bottom_text' => DB::raw('show_theater_name'),
        ]);

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'poster_fill_screen',
                'show_header_text',
                'show_theater_name',
                'theater_name',
                'theater_name_position',
            ]);
        });
    }
};
