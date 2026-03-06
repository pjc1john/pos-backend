<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            // Exactly one of product_id / variant_id is set per row
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->decimal('quantity', 12, 4)->default(1.0);
            $table->decimal('waste_factor', 8, 4)->default(0.0);
            $table->boolean('is_optional')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('variant_id')
                  ->references('id')->on('product_variants')
                  ->onDelete('cascade');

            $table->foreign('inventory_item_id')
                  ->references('id')->on('inventory_items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipe_items');
    }
};
