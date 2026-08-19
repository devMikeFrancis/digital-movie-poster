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
            $table->string('header_style', 20)->default('plain');
            $table->boolean('header_full_width')->default(false);
            $table->string('header_position', 10)->default('top');
            $table->boolean('theater_name_full_width')->default(false);
        });

        // The header's box was drawn by show_header_border, which is the same
        // thing the plaque style draws. Carried across so a display that had it
        // keeps it, rather than losing its box to a new default.
        DB::table('settings')->where('show_header_border', true)->update([
            'header_style' => 'plaque',
        ]);
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'header_style',
                'header_full_width',
                'header_position',
                'theater_name_full_width',
            ]);
        });
    }
};
