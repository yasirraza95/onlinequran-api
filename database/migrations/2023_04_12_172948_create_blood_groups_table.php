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
        if (!Schema::hasTable('blood_groups')) {
            Schema::create('blood_groups', function (Blueprint $table) {
                $table->id();
                $table->text('name');
                $table->timestamp('created_at')->useCurrent();
                $table->ipAddress('created_ip');
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
        Schema::dropIfExists('blood_groups');
    }
};