<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_logins', function (Blueprint $table) {
            $table->primary('log_login_id');
            $table->integer('user_id')->nullable();
            $table->bigInteger('member_id')->nullable();
            $table->string('member_no', 50)->nullable();
            $table->integer('log_state')->nullable();
            $table->integer('block_state')->nullable();
            $table->string('imei', 50)->nullable();
            $table->integer('log_change_password_status')->nullable();
            $table->text('log_login_remark')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_logins');
    }
};
