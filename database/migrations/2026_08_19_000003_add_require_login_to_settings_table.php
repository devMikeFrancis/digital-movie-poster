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
            $table->boolean('require_login')->default(true);
        });

        // Seeded from whatever the device is running on now, so taking this
        // update does not change who can reach the admin screens. DMP_REQUIRE_LOGIN
        // ships set in .env.example, so an install that had deliberately turned
        // it off would otherwise have had it turned back on underneath them.
        DB::table('settings')->update([
            'require_login' => (bool) config('dmp.auth.required'),
        ]);
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('require_login');
        });
    }
};
