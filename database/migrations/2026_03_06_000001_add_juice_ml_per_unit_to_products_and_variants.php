<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->double('juice_ml_per_unit')->nullable()->after('stock_alert_level');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->double('juice_ml_per_unit')->nullable()->after('stock_alert_level');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('juice_ml_per_unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('juice_ml_per_unit');
        });
    }
};
