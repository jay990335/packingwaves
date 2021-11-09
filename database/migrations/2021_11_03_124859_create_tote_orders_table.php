<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToteOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tote_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->unsignedBigInteger('totes_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('updated_by')->index();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tote_orders', function (Blueprint $table) {
            $table->dropForeign(['totes_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('tote_orders');
    }
}
