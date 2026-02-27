<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('category');
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit');
            $table->decimal('min_stock_level', 12, 4)->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('cost_per_unit', 12, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
