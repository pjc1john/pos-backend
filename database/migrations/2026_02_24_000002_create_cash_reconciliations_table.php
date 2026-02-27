<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->unsignedBigInteger('subscriber_id')->default(0);
            $table->date('date');
            $table->double('system_total');
            $table->double('counted_total');
            $table->double('difference');
            $table->unsignedInteger('attempts')->default(1);
            $table->string('reconciled_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscriber_id')->references('id')->on('subscribers');
            $table->index('subscriber_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_reconciliations');
    }
};
