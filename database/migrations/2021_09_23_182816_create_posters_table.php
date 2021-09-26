<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posters', function (Blueprint $table) {
            $table->id();
            $table->string('object_id')->nullable();
            $table->string('name')->nullable();
            $table->string('file_name');
            $table->boolean('show_in_rotation')->default(true);
            $table->integer('ordinal')->default(0);
            $table->timestamps();
            $table->index(['object_id', 'ordinal', 'show_in_rotation', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posters');
    }
}
