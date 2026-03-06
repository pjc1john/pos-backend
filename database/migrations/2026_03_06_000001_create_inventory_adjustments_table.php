<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->string('type'); // 'add' | 'remove' | 'set'
            $table->decimal('quantity_before', 12, 4);
            $table->decimal('quantity_change', 12, 4);
            $table->decimal('quantity_after', 12, 4);
            $table->string('reason');
            $table->string('adjusted_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_item_id')
                  ->references('id')
                  ->on('inventory_items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
