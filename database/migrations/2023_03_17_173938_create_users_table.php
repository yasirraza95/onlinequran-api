<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // $table->string('username', 255);
            $table->string('phone', 255)->nullable()->default(NULL);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->bigInteger('city_id')->unsigned()->nullable()->default(NULL);
            $table->bigInteger('state_id')->unsigned()->nullable()->default(NULL);
            $table->text('address');
            $table->enum('user_type', ['donor', 'recipient', 'both'])->default('donor');
            $table->enum('consent', ['yes', 'no'])->default('no');
            $table->enum('notifications', ['yes', 'no'])->default('no');
            $table->bigInteger('blood_group')->nullable()->default(NULL);
            $table->date('dob')->nullable()->default(NULL);
            $table->date('last_bleed')->nullable()->default(NULL);
            $table->string('reset_token', 255)->nullable()->default(NULL);
            $table->datetime('reset_tkn_time')->nullable()->default(NULL);
            $table->enum('reset_tkn_status', ['active', 'disabled'])->nullable()->default(NULL);
            $table->string('email_token', 255);
            $table->enum('email_status', ['active', 'disabled'])->default('disabled');
            $table->string('sms_token', 255);
            $table->datetime('sms_tkn_time')->nullable()->default(NULL);
            $table->enum('sms_status', ['active', 'disabled'])->default('disabled');
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->unsigned()->nullable()->default(NULL);
            $table->ipAddress('created_ip');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->bigInteger('updated_by')->unsigned()->nullable()->default(NULL);
            $table->ipAddress('updated_ip')->nullable()->default(NULL);
            $table->timestamp('deleted_at')->nullable()->default(NULL);
            $table->bigInteger('deleted_by')->unsigned()->nullable()->default(NULL);
            $table->ipAddress('deleted_ip')->nullable()->default(NULL);

            $table->foreign('city_id', 'user_city')->references('id')->on('cities')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('state_id', 'user_state')->references('id')->on('states')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}