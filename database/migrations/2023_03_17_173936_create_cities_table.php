<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('latitude', 255);
            $table->string('longitude', 255);
            $table->bigInteger('state_id')->unsigned();

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

            $table->foreign('state_id', 'city_state')->references('id')->on('states')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cities');
    }
}
