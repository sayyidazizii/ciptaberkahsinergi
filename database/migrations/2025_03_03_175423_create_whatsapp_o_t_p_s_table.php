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
        Schema::create('whatsapp_otp', function (Blueprint $table) {
            $table->id('otp_id');
            $table->integer('otp_code')->default(0)->nullable();
            $table->bigInteger('member_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('data_state')->default(0)->nullable();
            $table->integer('created_id')->default(0)->nullable();
            $table->dateTime('created_on')->nullable();
            $table->timestamp('last_update')->useCurrent()->nullable();
            $table->index('otp_code', 'FK_ppob_topup_branch_branch_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_otp');
    }
};
