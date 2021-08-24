<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserDetailsToLinnworksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('linnworks', function (Blueprint $table) {
            $table->string('linnworks_user_id')->after('passportAccessToken');
            $table->string('linnworks_email')->after('linnworks_user_id');
            $table->unsignedBigInteger('user_id')->index()->after('linnworks_email');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('linnworks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
}
