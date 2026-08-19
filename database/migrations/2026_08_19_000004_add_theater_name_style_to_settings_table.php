<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Defaults to plain, so a display already showing its name looks
            // exactly as it did until someone chooses otherwise.
            $table->string('theater_name_style', 20)->default('plain');
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('theater_name_style');
        });
    }
};
