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
        Schema::create('wa_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('broadcast_title',200)->nullable();
            $table->text('broadcast_message')->nullable();
            $table->string('broadcast_link',200)->nullable();
            $table->string('broadcast_mode',200)->nullable();
            $table->string('broadcast_type',200)->nullable();
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
