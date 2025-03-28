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
        Schema::create('ppob_transaction_refund_log', function (Blueprint $table) {
            $table->bigIncrements('ppob_transaction_refund_log_id');
            $table->unsignedBigInteger('ppob_transaction_id')->nullable();
            $table->tinyInteger('ppob_transaction_status')->nullable();
            $table->tinyInteger('ppob_refund_status')->nullable();
            $table->string('ppob_transaction_refund_remark', 250)->nullable();
            $table->integer('created_id')->nullable();
            $table->integer('updated_id')->nullable();
            $table->integer('deleted_id')->nullable();
            $table->string('ppob_transaction_refund_amount', 50)->nullable();
            $table->foreign('ppob_transaction_id')
                ->references('ppob_transaction_id')
                ->on('ppob_transaction')
                ->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_transaction_refund_log');
    }
};
