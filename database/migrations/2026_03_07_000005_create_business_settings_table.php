<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->nullable()->unique();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscriber_id', 'branch_id', 'setting_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
