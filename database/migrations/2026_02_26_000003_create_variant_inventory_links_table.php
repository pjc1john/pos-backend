<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_inventory_links', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity_per_unit', 12, 4)->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['variant_id', 'inventory_item_id']);
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_inventory_links');
    }
};
