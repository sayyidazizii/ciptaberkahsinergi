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
        Schema::create('ppob_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('ppob_product_category_code', 25)->default('');
            $table->string('ppob_product_category_name', 250)->default('');
            $table->tinyInteger('ppob_product_category_api_type')->default(0);
            $table->integer('created_id')->default(0);
            $table->integer('updated_id')->default(0);
            $table->integer('deleted_id')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_p_o_b_product_categories');
    }
};
