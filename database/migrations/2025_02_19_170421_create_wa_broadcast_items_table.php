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
        Schema::create('wa_broadcast_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("member_id")->nullable();
            $table->unsignedBigInteger("user_id_mobile")->nullable();
            $table->unsignedBigInteger('wa_broadcast_id')->nullable();
            $table->foreign('wa_broadcast_id')->references('id')->on('wa_broadcasts')->onUpdate('cascade');
            $table->unsignedBigInteger("created_id")->nullable();
            $table->unsignedBigInteger("updated_id")->nullable();
            $table->unsignedBigInteger("deleted_id")->nullable();
            $table->softDeletesTz();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_broadcasts');
    }
};
