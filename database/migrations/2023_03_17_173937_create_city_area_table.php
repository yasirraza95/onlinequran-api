<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_area', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->bigInteger('city_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            // $table->bigInteger('created_by')->unsigned();
            $table->ipAddress('created_ip');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            // $table->bigInteger('updated_by')->unsigned()->nullable()->default(NULL);
            $table->ipAddress('updated_ip')->nullable()->default(NULL);
            $table->timestamp('deleted_at')->nullable()->default(NULL);
            // $table->bigInteger('deleted_by')->unsigned()->nullable()->default(NULL);
            $table->ipAddress('deleted_ip')->nullable()->default(NULL);

            // $table->foreign('created_by', 'user_states_users')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
            // $table->foreign('updated_by', 'user_states_users1')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
            // $table->foreign('deleted_by', 'user_states_users2')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');

            $table->foreign('city_id', 'cityarea_city')->references('id')->on('cities')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('city_area');
    }
};