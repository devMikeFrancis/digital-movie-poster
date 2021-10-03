<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDecodingColumnsToPostersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posters', function (Blueprint $table) {
            $table->boolean('show_dolby_atmos')->default(false);
            $table->boolean('show_dolby_51')->default(false);
            $table->boolean('show_dolby_vision')->default(false);
            $table->boolean('show_dtsx')->default(false);
            $table->boolean('show_auro_3d')->default(false);
            $table->boolean('show_imax')->default(false);
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
