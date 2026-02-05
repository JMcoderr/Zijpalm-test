<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\ActivityType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->dateTime('registrationStart')->nullable();
            $table->dateTime('registrationEnd')->nullable();
            $table->dateTime('cancellationEnd')->nullable();
            $table->string('location');
            $table->string('organizer');
            $table->string('imagePath')->nullable();
            $table->decimal('price', 8, 2)->default(0.00);
            $table->integer('maxParticipants')->nullable();
            $table->integer('maxGuests')->nullable();
            $table->string('whatsappUrl')->nullable();
            $table->enum('type', ActivityType::toArray())->default(ActivityType::OneDay);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
