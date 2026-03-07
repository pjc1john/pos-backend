<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->nullable()->unique();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('assigned_date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscriber_id', 'user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_branches');
    }
};
