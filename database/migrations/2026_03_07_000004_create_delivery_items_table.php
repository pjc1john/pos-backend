<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->nullable()->unique();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('item_name');
            $table->double('quantity');
            $table->string('unit');
            $table->string('batch_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
