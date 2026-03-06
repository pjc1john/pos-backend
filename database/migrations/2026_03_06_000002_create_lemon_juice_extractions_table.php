<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lemon_juice_extractions', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->default(0)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->date('date')->index();
            $table->double('amount_ml');
            $table->double('lemons_for_extraction')->nullable();
            $table->double('lemons_for_slices')->nullable();
            // Stores the server-side inventory_item ID; nullable — FK resolved during sync
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscriber_id')->references('id')->on('subscribers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lemon_juice_extractions');
    }
};
