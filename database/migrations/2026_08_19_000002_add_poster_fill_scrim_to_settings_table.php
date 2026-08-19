<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // How heavily to shade the top and bottom of a filled poster so the
            // header and footer text stays readable over the artwork.
            $table->string('poster_fill_scrim', 20)->default('standard');
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('poster_fill_scrim');
        });
    }
};
