<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->default(0);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('sale_sync_id')->nullable();   // UUID of the associated sale
            $table->string('receipt_number')->nullable();
            $table->string('requested_by');
            $table->timestamp('requested_at')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('subscriber_id');
            $table->index('branch_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_requests');
    }
};
