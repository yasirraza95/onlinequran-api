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
        if (!Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('request_type');
                $table->string('ip_address');
                $table->string('endpoint')->nullable()->default(NULL);
                $table->text('referer_link')->nullable()->default(NULL);
                $table->string('browser');
                $table->text('authorization')->nullable()->default(NULL);
                $table->text('form_data')->nullable()->default(NULL);
                $table->text('response')->nullable()->default(NULL);
                $table->text('http_status')->nullable()->default(NULL);
                $table->timestamps();
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
        Schema::dropIfExists('logs');
    }
};