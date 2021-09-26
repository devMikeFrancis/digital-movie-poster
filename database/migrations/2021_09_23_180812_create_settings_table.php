<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('plex_ip_address', 30)->nullable();
            $table->string('plex_token', 100)->nullable();
            $table->integer('poster_display_limit')->default(20);
            $table->integer('poster_display_speed')->default(15000);
            $table->string('coming_soon_text', 40)->nullable();
            $table->string('now_playing_text', 40)->nullable();
            $table->string('bottom_text', 60)->nullable();
            $table->boolean('show_bottom_text')->default(false);
            $table->boolean('show_mpaa_rating')->default(true);
            $table->boolean('show_audience_rating')->default(true);
            $table->boolean('show_processing_logos')->default(true);
            $table->string('video_logo', 30)->default('dolby_vision');
            $table->string('audio_logo', 30)->default('dolby_atmos');
            $table->boolean('use_cec_power')->default(true);
            $table->string('start_power_time', 10)->default('09:00:00');
            $table->string('end_power_time', 10)->default('23:00:00');
        });

        app('db')->insert("insert into settings (coming_soon_text, now_playing_text) values (?, ?)", [
            'Coming Soon',
            'Now Playing',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
