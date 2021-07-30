<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHasPrintButtonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_has_print_buttons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('print_buttons_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            
            $table->foreign('print_buttons_id')->references('id')->on('print_buttons');
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
        Schema::table('user_has_print_buttons', function (Blueprint $table) {
            $table->dropForeign(['print_buttons_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('user_has_print_buttons');
    }
}
