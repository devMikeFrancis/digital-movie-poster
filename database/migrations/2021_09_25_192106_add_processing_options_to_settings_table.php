<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessingOptionsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('show_dolby_atmos_horizontal')->default(true);
            $table->boolean('show_dolby_atmos_vertical')->default(true);
            $table->boolean('show_dts')->default(false);
            $table->boolean('show_imax')->default(false);
            $table->boolean('show_auro_3d')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
