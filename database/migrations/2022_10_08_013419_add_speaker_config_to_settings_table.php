<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('speaker_config', 20)->nullable();
            $table->boolean('show_speaker_config')->nullable()->default(false);
            $table->string('speaker_config_location', 50)->default('bottom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('speaker_config');
            $table->dropColumn('speaker_config_location');
            $table->dropColumn('show_speaker_config');
        });
    }
};
