<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->string("sync_status")->nullable();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('quantity');
            $table->double('unit_price');
            $table->double('total_price');
            $table->timestamps();
            $table->softDeletes();

            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
