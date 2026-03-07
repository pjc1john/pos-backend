<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_config', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->nullable()->unique();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscriber_id', 'branch_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_config');
    }
};
