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
        Schema::create('hunting_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('tour_name');
            $table->string('hunter_name');
            $table->foreignId('guide_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('participants_count');
            $table->timestamps();

            // Index for checking guide availability by date
            $table->index(['guide_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hunting_bookings');
    }
};
