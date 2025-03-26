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
        Schema::table('core_anouncements', function (Blueprint $table) {
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('recuring')->nullable();
            $table->string('url')->nullable();
            $table->text('recuring_type')->nullable()->comment("daily, weekly, monthly, yearly, custom");
            $table->integer('recuring_interval')->nullable();
            $table->integer('recuring_day')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_anouncements', function (Blueprint $table) {
            //
        });
    }
};
