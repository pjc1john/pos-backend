<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique()->nullable();
            $table->string('sync_status')->default('pending');
            $table->unsignedBigInteger('subscriber_id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscriber_id')->references('id')->on('subscribers');
            $table->index('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
