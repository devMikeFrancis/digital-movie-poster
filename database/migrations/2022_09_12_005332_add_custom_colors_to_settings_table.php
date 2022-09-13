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
            $table->string('header_bg_color', 15)->default('#000000');
            $table->string('header_text_color', 15)->default('#ffffff');
            $table->string('header_border_color', 15)->default('#ffffff');
            $table->string('header_font', 50)->default('default');
            $table->string('header_font_size', 15)->default('normal');
            $table->boolean('show_header_border')->default(true);
            $table->string('footer_bg_color', 15)->default('#000000');
            $table->string('footer_text_color', 15)->default('#ffffff');
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
};
