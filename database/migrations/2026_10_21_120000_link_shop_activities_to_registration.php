<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->boolean('normal_registration')->default(false);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('shop_order_id')->nullable()->unique()->constrained('orders')->nullOnDelete();
            $table->decimal('shop_unit_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['shop_order_id']);
            $table->dropUnique(['shop_order_id']);
            $table->dropColumn(['shop_order_id', 'shop_unit_price']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropUnique(['activity_id']);
            $table->dropColumn(['activity_id', 'normal_registration']);
        });
    }
};