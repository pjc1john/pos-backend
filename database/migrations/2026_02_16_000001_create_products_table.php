<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->default(0);
            $table->string('name');
            $table->double('price');
            $table->double('cost_price')->nullable();
            $table->string('category');
            $table->text('description')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('stock_alert_level')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscriber_id')->references('id')->on('subscribers');
            $table->index('subscriber_id');
            $table->index('category');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->double('price_modifier')->default(0.0);
            $table->double('price')->nullable();
            $table->double('cost_price')->nullable();
            $table->integer('stock')->nullable();
            $table->integer('stock_alert_level')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
