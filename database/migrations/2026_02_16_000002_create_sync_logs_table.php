<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 100);
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->string('operation');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
