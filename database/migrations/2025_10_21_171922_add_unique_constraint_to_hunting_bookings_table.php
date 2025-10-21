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
        Schema::table('hunting_bookings', function (Blueprint $table) {
            // Drop the existing index first
            $table->dropIndex(['guide_id', 'date']);

            // Add unique constraint to prevent double booking
            // A guide can have only one booking per date
            $table->unique(['guide_id', 'date'], 'unique_guide_date_booking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hunting_bookings', function (Blueprint $table) {
            // Remove the unique constraint
            $table->dropUnique('unique_guide_date_booking');

            // Restore the original index
            $table->index(['guide_id', 'date']);
        });
    }
};
