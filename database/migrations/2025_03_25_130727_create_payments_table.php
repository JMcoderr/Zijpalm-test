<?php

use App\PaymentStatus;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete(); // The id of the application
            $table->string('mollieId'); // The id of the payment in Mollie
            $table->boolean('isPaymentLink')->default(false);
            $table->string('description'); // The description of the payment
            $table->enum('status', PaymentStatus::toArray()); // The status of the payment in Mollie
            $table->decimal('price', 8, 2); // The price of the payment
            $table->dateTime('paidAt')->nullable(); // The date and time of the payment
            $table->decimal('refundedAmount', 8, 2)->nullable(); // The refunded amount of the payment
            $table->dateTime('refundedAt')->nullable(); // The date and time of the refund
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
