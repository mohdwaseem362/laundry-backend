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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();

            // Owner
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Reference to master pincode table
            $table->foreignId('pincode_id')->nullable()->constrained('pincodes')->nullOnDelete();

            // Address fields
            $table->string('label')->nullable();   // e.g. Home, Office
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            // Keep country_id optional if user moves cross-border
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();

            // Geo data
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Status
            $table->boolean('is_primary')->default(false);
            $table->json('meta')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Useful indexes
            $table->index(['user_id']);
            $table->index(['pincode_id']);
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
