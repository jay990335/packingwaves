<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHasShipmentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_has_shipment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_settings_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();

            $table->foreign('shipment_settings_id')->references('id')->on('shipment_settings');
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
        Schema::table('user_has_shipment_settings', function (Blueprint $table) {
            $table->dropForeign(['shipment_settings_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('user_has_shipment_settings');
    }
}
