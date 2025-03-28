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
        Schema::create('ppob_transaction', function (Blueprint $table) {
            $table->id("ppob_transaction_id");
            $table->string('ppob_transaction_no', 100)->nullable();
            $table->string('trxID', 100)->nullable();
            $table->string('ppob_unique_code', 250)->nullable();
            $table->bigInteger('ppob_company_id')->nullable();
            $table->bigInteger('ppob_agen_id')->nullable();
            $table->string('ppob_agen_name', 250)->nullable();
            $table->integer('ppob_product_category_id')->nullable();
            $table->bigInteger('ppob_product_id')->nullable();
            $table->bigInteger('savings_account_id')->nullable();
            $table->integer('savings_id')->nullable();
            $table->bigInteger('member_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->bigInteger('transaction_id')->nullable();
            $table->string('imei', 250)->nullable();
            $table->decimal('ppob_transaction_amount', 20, 2)->nullable();
            $table->decimal('ppob_transaction_default_amount', 20, 2)->nullable();
            $table->decimal('ppob_transaction_admin_amount', 20, 2)->nullable();
            $table->decimal('ppob_transaction_company_amount', 20, 2)->nullable();
            $table->decimal('ppob_transaction_fee_amount', 20, 2)->nullable();
            $table->decimal('ppob_transaction_commission_amount', 20, 2)->nullable();
            $table->date('ppob_transaction_date')->nullable();
            $table->tinyInteger('ppob_transaction_status')->default(0)->nullable();
            $table->tinyInteger('sanbox_status')->default(0)->nullable();
            $table->text('ppob_transaction_remark')->nullable();
            $table->string('ppob_transaction_token', 250)->nullable()->unique();
            $table->integer('created_id')->nullable();
            $table->integer('updated_id')->nullable();
            $table->integer('deleted_id')->nullable();
            $table->string('ppob_transaction_title', 100)->nullable();

            $table->index('ppob_company_id');
            $table->index('ppob_product_category_id');
            $table->index('ppob_unique_code', 'ppob_trxID');
            $table->index('transaction_id');
            $table->index('member_id');
            $table->index('branch_id');
            $table->index('ppob_agen_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_transaction');
    }
};
