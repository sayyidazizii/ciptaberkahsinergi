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
        if (!Schema::hasTable('ppob_topup_branch')) {
            Schema::create('ppob_topup_branch', function (Blueprint $table) {
            $table->bigIncrements('topup_branch_id');
            $table->integer('branch_id')->default(0);
            $table->decimal('topup_branch_balance', 20, 2)->default(0.00);
            $table->integer('created_id')->default(0);
            $table->integer('updated_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_topup_branch');
    }
};
