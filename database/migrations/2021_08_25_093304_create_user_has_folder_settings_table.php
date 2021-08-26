<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHasFolderSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_has_folder_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_settings_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();

            $table->foreign('folder_settings_id')->references('id')->on('folder_settings');
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
        Schema::table('user_has_folder_settings', function (Blueprint $table) {
            $table->dropForeign(['folder_settings_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('user_has_folder_settings');
    }
}
