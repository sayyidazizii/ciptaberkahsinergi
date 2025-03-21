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
        Schema::create('system_mobile_user', function (Blueprint $table) {
            $table->unsignedBigInteger( 'user_id')->primary();
            $table->integer('member_id')->default(0);
            $table->integer('branch_id')->nullable();
            $table->uuid('uuid')->nullable();
            $table->string('member_no', 30)->nullable();
            $table->text('password')->nullable();
            $table->text('password_transaksi')->nullable();
            $table->text('member_name')->nullable();
            $table->string('member_phone', 50)->default('081');
            $table->string('member_imei', 250)->default('');
            $table->tinyInteger('member_user_status')->default(0);
            $table->string('member_token', 250)->nullable();
            $table->tinyInteger('log_state')->default(0)->comment('0: Not Login, 1: Login,2 : Active (pernah ganti password), 3: inactive for 30 days');
            $table->tinyInteger('block_state')->default(0);
            $table->tinyInteger('otp_state')->default(0);
            $table->dateTime('expired_on')->nullable();
            $table->string('username', 50)->nullable();
            $table->string('email', 191)->nullable()->unique();
            $table->string('system_version', 20)->default('1.0.0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('avatar', 50)->nullable();
            $table->string('google_id', 191)->nullable()->unique();
            $table->string('google_avatar', 250)->nullable();
            $table->string('google_avatar_original', 250)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('updated_id')->nullable();
            $table->unsignedBigInteger('deleted_id')->nullable();
            $table->timestamp("last_login")->nullable();
            $table->timestamps();
            $table->softDeletesTz();
            $table->unique('email', 'system_user_email_unique');
            $table->index('member_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_user_android');
    }
};
