<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_guest_checkout')->default(false)->after('user_id');
        });

        // Backfill older orders so admin sorting immediately becomes clearer.
        $orders = DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select('orders.id', 'users.password')
            ->get();

        foreach ($orders as $order) {
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'is_guest_checkout' => $order->password === null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_guest_checkout');
        });
    }
};
