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
        if (!Schema::hasTable('blood_request')) {
            Schema::create('blood_request', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['normal', 'emergency'])->default('normal');
                $table->bigInteger('blood_id')->unsigned();
                $table->bigInteger('state_id')->unsigned();
                $table->bigInteger('city_id')->unsigned();
                $table->bigInteger('city_area_id')->unsigned();
                $table->bigInteger('created_by')->unsigned();
                $table->timestamp('created_at')->useCurrent();
                $table->ipAddress('created_ip');
                $table->timestamp('deleted_at')->nullable()->default(NULL);
                $table->ipAddress('deleted_ip')->nullable()->default(NULL);

                $table->foreign('created_by', 'request_users')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
                $table->foreign('state_id', 'request_state')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
                $table->foreign('city_id', 'request_cities')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
                $table->foreign('city_area_id', 'request_city_area')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blood_request');
    }
};