<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique()->nullable();
            $table->string("sync_status")->nullable();
            $table->foreignId('subscriber_id')->default(0)->constrained('subscribers')->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->double('total_amount');
            $table->double('discount_amount')->default(0);
            $table->double('final_amount');
            $table->string('payment_method');
            $table->double('amount_received')->default(0);
            $table->double('change_amount')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('subscriber_id');
            $table->index('receipt_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
