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
        Schema::create('core_anouncements', function (Blueprint $table) {
            $table->id();
            $table->string('title',200)->nullable();
            $table->text('message')->nullable();
            $table->string('link',200)->nullable();
            $table->string('image',200)->nullable();
            $table->string('type',50)->nullable()->comment("Info, Warning, Danger, Success");
            $table->tinyInteger('broadcast_type')->nullable()->comment("1: All, 2: Specific Users, 3: Specific Roles");
            $table->string('image_gallery',200)->nullable();
            $table->string('broadcast_link',200)->nullable();
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
        Schema::dropIfExists('core_anouncements');
    }
};
