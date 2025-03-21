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
        Schema::create('core_anouncement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anouncement_id')->nullable();
            $table->foreign('anouncement_id')->references('id')->on('core_anouncements')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title',200)->nullable();
            $table->text('message')->nullable();
            $table->string('link',200)->nullable();
            $table->string('image',200)->nullable();
            $table->string('type',50)->nullable()->comment("Info, Warning, Danger, Success");
            $table->integer("member_id")->nullable();
            $table->integer("user_id")->nullable();
            $table->tinyInteger('is_active')->nullable();
            $table->tinyInteger('should_broadcast')->nullable()->comment("0: No, 1: Yes, Should broadcast this message using pusher/reverb");
            $table->softDeletesTz();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_anouncement_items');
    }
};
