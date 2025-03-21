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
        Schema::create('whatsapp_otps', function (Blueprint $table) {
            $table->unsignedBigInteger('otp_id')->primary();
            $table->uuid('uuid')->nullable();
            $table->integer('otp_code')->default(0)->nullable();
            $table->bigInteger('member_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('created_id')->default(0)->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->index('otp_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_otps');
    }
};
