<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->nullable()->unique();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('branch_name');
            $table->date('delivery_date');
            $table->string('status')->default('pending'); // pending, in_transit, delivered, cancelled
            $table->text('notes')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
