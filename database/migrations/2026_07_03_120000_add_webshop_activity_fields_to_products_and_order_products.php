<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('product')->after('name');
            $table->decimal('guest_price', 8, 2)->nullable()->after('price');
            $table->boolean('allow_guest_checkout')->default(false)->after('hidden');
            $table->dateTime('activity_starts_at')->nullable()->after('pickupLocation');
            $table->dateTime('activity_ends_at')->nullable()->after('activity_starts_at');
            $table->dateTime('signup_deadline')->nullable()->after('activity_ends_at');
            $table->unsignedInteger('max_participants')->nullable()->after('stock');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->decimal('unit_price', 8, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'guest_price',
                'allow_guest_checkout',
                'activity_starts_at',
                'activity_ends_at',
                'signup_deadline',
                'max_participants',
            ]);
        });
    }
};
