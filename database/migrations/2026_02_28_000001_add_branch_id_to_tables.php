<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need branch_id added.
     * Tables already having branch_id (expenses, cash_reconciliations, dtr, inventory_items, sales)
     * and tables that are the branch themselves (branches) are excluded.
     */
    public function up(): void
    {
        // products
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
            $table->index('branch_id');
        });

        // product_variants — also add subscriber_id for direct scoping
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('subscriber_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
        });

        // discounts
        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
            $table->index('branch_id');
        });

        // sale_items — add subscriber_id + branch_id
        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedBigInteger('subscriber_id')->nullable()->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
        });

        // product_inventory_links
        Schema::table('product_inventory_links', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
        });

        // variant_inventory_links
        Schema::table('variant_inventory_links', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::table('variant_inventory_links', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('product_inventory_links', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['subscriber_id', 'branch_id']);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['subscriber_id', 'branch_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
